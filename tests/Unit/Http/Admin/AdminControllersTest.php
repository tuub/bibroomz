<?php

covers(
    App\Http\Controllers\Admin\UserController::class,
    App\Http\Controllers\Admin\SettingController::class,
    App\Http\Controllers\Admin\ClosingController::class,
    App\Http\Controllers\Admin\MailController::class,
    App\Http\Controllers\Admin\ResourceController::class,
    App\Http\Controllers\Admin\ResourceGroupController::class,
    App\Http\Controllers\Admin\InstitutionController::class,
    App\Http\Controllers\Admin\HappeningController::class,
    App\Http\Controllers\Admin\AdminController::class,
    App\Services\Admin\UserAdminService::class,
    App\Services\Admin\SettingAdminService::class,
    App\Services\Admin\SettingableResolver::class,
    App\Services\Admin\ClosingAdminService::class,
    App\Services\Admin\ResourceAdminService::class,
    App\Services\Admin\ResourceGroupAdminService::class,
    App\Services\Admin\InstitutionAdminService::class,
    App\Services\Admin\HappeningAdminService::class,
    App\Services\Admin\RoleAdminService::class,
    App\Services\Admin\UserGroupAdminService::class,
    App\Services\Admin\UserRoleSynchronizer::class,
    App\Services\Admin\MailAdminService::class
);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Library\Utility;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Permission;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Admin\UserAdminService;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
});

function translatable(string $value): array
{
    return Utility::getTranslatable($value);
}

function actingAdmin(): User
{
    $institution = Institution::factory()->create();
    $admin = User::factory()->create([
        'is_admin' => true,
        'is_system_user' => true,
    ]);
    $role = Role::create([
        'name' => translatable('Admin Access'),
    ]);
    $role->permissions()->attach(Permission::query()->firstOrFail());
    $admin->roles()->attach($role->id, ['institution_id' => $institution->id]);

    test()->actingAs($admin);

    return $admin;
}

test('admin dashboard routes preserve their payloads and redirects', function () {
    actingAdmin();

    $weekDayIds = \App\Models\WeekDay::query()->pluck('id')->all();

    $response = $this->get('/admin');
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));

    $indexResponse = $this->get(route('admin.institution.index'));
    $indexResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Index')
            ->has('institutions'));

    $createInstitutionResponse = $this->get(route('admin.institution.create'));
    $createInstitutionResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Form')
            ->has('daysOfWeek')
            ->has('languages'));

    $this->post(route('admin.institution.store'), [
        'title' => translatable('Test Institution'),
        'short_title' => 'TI',
        'slug' => 'test-institution',
        'location' => 'Berlin',
        'week_days' => $weekDayIds,
        'home_uri' => 'https://example.org',
        'logo_uri' => 'https://example.org/logo.png',
        'teaser_uri' => 'https://example.org/teaser.png',
        'email' => 'info@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    $institution = Institution::query()->where('slug', 'test-institution')->firstOrFail();

    $editInstitutionResponse = $this->get(route('admin.institution.edit', ['id' => $institution->id]));
    $editInstitutionResponse->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Form')
            ->where('institution.id', $institution->id)
            ->has('daysOfWeek'));

    $this->post(route('admin.institution.update'), [
        'id' => $institution->id,
        'title' => translatable('Updated Institution'),
        'short_title' => 'UIT',
        'slug' => 'updated-institution',
        'location' => 'Potsdam',
        'week_days' => array_slice($weekDayIds, 0, 5),
        'home_uri' => 'https://example.org/home',
        'logo_uri' => 'https://example.org/logo-2.png',
        'teaser_uri' => 'https://example.org/teaser-2.png',
        'email' => 'updated@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    expect($institution->fresh()->slug)->toBe('updated-institution');

    $this->post(route('admin.institution.order'), [
        ['id' => $institution->id, 'order' => 7],
    ])->assertOk();

    expect($institution->fresh()->order)->toBe(7);

    $resourceGroupIndex = $this->get(route('admin.resource_group.index', [
        'institution_id' => $institution->id,
    ]));
    $resourceGroupIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Index')
            ->where('institution.id', $institution->id)
            ->has('resource_groups'));

    $resourceGroupCreate = $this->get(route('admin.resource_group.create', [
        'institution_id' => $institution->id,
    ]));
    $resourceGroupCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Form')
            ->where('institution.id', $institution->id)
            ->has('institutions'));

    $this->post(route('admin.resource_group.store'), [
        'institution_id' => $institution->id,
        'title' => translatable('Rooms'),
        'slug' => 'rooms',
        'term_singular' => translatable('Room'),
        'term_plural' => translatable('Rooms'),
        'description' => translatable('Available rooms'),
        'is_active' => true,
        'user_groups' => [],
        'help_uri' => 'https://example.org/help',
    ])->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    $resourceGroup = ResourceGroup::query()->where('slug', 'rooms')->firstOrFail();

    $resourceGroupEdit = $this->get(route('admin.resource_group.edit', ['id' => $resourceGroup->id]));
    $resourceGroupEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Form')
            ->where('resource_group.id', $resourceGroup->id)
            ->has('institutions'));

    $this->post(route('admin.resource_group.update'), [
        'id' => $resourceGroup->id,
        'institution_id' => $institution->id,
        'title' => translatable('Study Rooms'),
        'slug' => 'study-rooms',
        'term_singular' => translatable('Study room'),
        'term_plural' => translatable('Study rooms'),
        'description' => translatable('Updated description'),
        'is_active' => true,
        'user_groups' => [],
        'help_uri' => 'https://example.org/help-2',
    ])->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    expect($resourceGroup->fresh()->slug)->toBe('study-rooms');

    $this->post(route('admin.resource_group.order'), [
        ['id' => $resourceGroup->id, 'order' => 4],
    ])->assertOk();

    expect($resourceGroup->fresh()->order)->toBe(4);

    $resourceIndex = $this->get(route('admin.resource.index', [
        'resource_group_id' => $resourceGroup->id,
    ]));
    $resourceIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Index')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->has('resources'));

    $resourceCreate = $this->get(route('admin.resource.create', [
        'resource_group_id' => $resourceGroup->id,
    ]));
    $resourceCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Form')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->has('weekDays'));

    $businessHourId = (string) Str::uuid();
    $this->post(route('admin.resource.store'), [
        'resource_group_id' => $resourceGroup->id,
        'title' => translatable('Desk A'),
        'location' => translatable('First Floor'),
        'location_uri' => 'https://example.org/map',
        'description' => translatable('Quiet desk'),
        'capacity' => 2,
        'is_active' => true,
        'is_verification_required' => false,
        'business_hours' => [[
            'id' => $businessHourId,
            'start' => '08:00',
            'end' => '18:00',
            'week_days' => $weekDayIds,
            'start_date' => null,
            'end_date' => null,
        ]],
    ])->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));

    $resource = Resource::query()->where('resource_group_id', $resourceGroup->id)->where('capacity', 2)->firstOrFail();
    $resourceBusinessHour = $resource->business_hours()->firstOrFail();

    $resourceEdit = $this->get(route('admin.resource.edit', ['id' => $resource->id]));
    $resourceEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Form')
            ->where('resource.id', $resource->id)
            ->has('resource.business_hours', 1));

    $this->post(route('admin.resource.update'), [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => translatable('Desk B'),
        'location' => translatable('Second Floor'),
        'location_uri' => 'https://example.org/map-2',
        'description' => translatable('Updated quiet desk'),
        'capacity' => 4,
        'is_active' => true,
        'is_verification_required' => true,
        'business_hours' => [[
            'id' => $resourceBusinessHour->id,
            'start' => '09:00',
            'end' => '17:00',
            'week_days' => array_slice($weekDayIds, 0, 5),
            'start_date' => '01.06.2026',
            'end_date' => '30.06.2026',
        ]],
    ])->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));

    expect((int) $resource->fresh()->capacity)->toBe(4)
        ->and($resource->fresh()->is_verification_required)->toBeTrue();

    $this->post(route('admin.resource.order'), [
        ['id' => $resource->id, 'order' => 9],
    ])->assertOk();

    expect($resource->fresh()->order)->toBe(9);

    $cloneResponse = $this->post(route('admin.resource.clone'), ['id' => $resource->id]);
    $cloneResponse->assertRedirect();

    $clonedResource = Resource::query()
        ->where('resource_group_id', $resourceGroup->id)
        ->where('id', '!=', $resource->id)
        ->firstOrFail();

    expect($cloneResponse->headers->get('Location'))->toContain($clonedResource->id);

    $settingsIndex = $this->get(route('admin.setting.index', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
    ]));
    $settingsIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Index')
            ->where('settingable.id', $institution->id)
            ->where('settingable_type', 'institution')
            ->has('settings'));

    $setting = $institution->settings()->firstOrFail();

    $settingEdit = $this->get(route('admin.setting.edit', ['id' => $setting->id]));
    $settingEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Form')
            ->where('setting.id', $setting->id)
            ->where('settingable_type', 'institution'));

    $this->post(route('admin.setting.update'), [
        'id' => $setting->id,
        'key' => $setting->key,
        'value' => 'Europe/Paris',
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ])->assertRedirect(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]));

    expect($setting->fresh()->value)->toBe('Europe/Paris');

    $closingsIndex = $this->get(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));
    $closingsIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Closings/Index')
            ->where('closable.id', $institution->id)
            ->where('closable_type', 'institution'));

    $closingCreate = $this->get(route('admin.closing.create', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));
    $closingCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Closings/Form')
            ->where('closable.id', $institution->id)
            ->where('closable_type', 'institution'));

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
        'start_date' => '03.06.2026',
        'start_time' => '08:00',
        'end_date' => '03.06.2026',
        'end_time' => '10:00',
        'description' => translatable('Morning maintenance'),
    ])->assertRedirect(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));

    $closing = Closing::query()->where('closable_id', $institution->id)->firstOrFail();

    $closingEdit = $this->get(route('admin.closing.edit', ['id' => $closing->id]));
    $closingEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Closings/Form')
            ->where('closing.id', $closing->id)
            ->where('closable_type', 'institution'));

    $this->post(route('admin.closing.update'), [
        'id' => $closing->id,
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
        'start_date' => '03.06.2026',
        'start_time' => '09:00',
        'end_date' => '03.06.2026',
        'end_time' => '11:00',
        'description' => translatable('Shifted maintenance'),
    ])->assertRedirect(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));

    expect($closing->fresh()->getTranslation('description', 'en'))->toBe('Shifted maintenance');

    $this->post(route('admin.closing.delete'), [
        'id' => $closing->id,
    ])->assertRedirect(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));

    expect(Closing::query()->find($closing->id))->toBeNull();

    $this->post(route('admin.resource.delete'), ['id' => $clonedResource->id])
        ->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));
    $this->post(route('admin.resource.delete'), ['id' => $resource->id])
        ->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));
    $this->post(route('admin.resource_group.delete'), ['id' => $resourceGroup->id])
        ->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    $deleteTarget = Institution::factory()->create();
    $this->post(route('admin.institution.delete'), ['id' => $deleteTarget->id])
        ->assertRedirect(route('admin.institution.index'));

    expect(Institution::query()->find($deleteTarget->id))->toBeNull();
});

test('admin roles users and user groups routes preserve their payloads and mutations', function () {
    actingAdmin();

    $institution = Institution::factory()->create();
    $permission = Permission::query()->firstOrFail();

    $rolesIndex = $this->get(route('admin.role.index'));
    $rolesIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Index')
            ->has('roles'));

    $rolesCreate = $this->get(route('admin.role.create'));
    $rolesCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Form')
            ->has('permissions')
            ->has('groups'));

    $this->post(route('admin.role.store'), [
        'name' => translatable('Operators'),
        'description' => translatable('Resource operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    $role = Role::query()->get()->first(
        fn (Role $candidate): bool => $candidate->getTranslation('name', 'en') === 'Operators',
    );
    expect($role)->not->toBeNull();

    $roleEdit = $this->get(route('admin.role.edit', ['id' => $role->id]));
    $roleEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Roles/Form')
            ->where('role.id', $role->id));

    $this->post(route('admin.role.update'), [
        'id' => $role->id,
        'name' => translatable('Senior Operators'),
        'description' => translatable('Updated operators'),
        'permissions' => [$permission->id],
    ])->assertRedirect(route('admin.role.index'));

    expect($role->fresh()->getTranslation('name', 'en'))->toBe('Senior Operators');

    $usersIndex = $this->get(route('admin.user.index'));
    $usersIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Index')
            ->has('users'));

    $usersCreate = $this->get(route('admin.user.create'));
    $usersCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Form')
            ->has('roles')
            ->has('institutions'));

    $this->post(route('admin.user.store'), [
        'is_system_user' => true,
        'name' => 'local.admin.target',
        'email' => 'target@example.org',
        'is_set_password' => true,
        'password' => 'Secret123!',
        'password_confirm' => 'Secret123!',
        'is_admin' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ])->assertRedirect(route('admin.user.index'));

    $managedUser = User::query()->where('name', 'local.admin.target')->firstOrFail();

    expect(Hash::check('Secret123!', (string) $managedUser->password))->toBeTrue()
        ->and($managedUser->roles()->count())->toBe(1);

    $userEdit = $this->get(route('admin.user.edit', ['id' => $managedUser->id]));
    $userEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Users/Form')
            ->where('user.id', $managedUser->id)
            ->has('roles')
            ->has('institutions'));

    $this->post(route('admin.user.update'), [
        'id' => $managedUser->id,
        'is_system_user' => true,
        'name' => 'local.admin.updated',
        'email' => 'updated-target@example.org',
        'is_set_password' => false,
        'is_admin' => false,
        'roles' => [[
            'role_id' => $role->id,
            'institution_id' => $institution->id,
        ]],
    ])->assertRedirect(route('admin.user.index'));

    expect($managedUser->fresh()->name)->toBe('local.admin.updated');

    $this->post(route('admin.user.ban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));
    expect($managedUser->fresh()->isBanned())->toBeTrue();

    $this->post(route('admin.user.unban'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));
    expect($managedUser->fresh()->isBanned())->toBeFalse();

    $userGroupIndex = $this->get(route('admin.user_group.index'));
    $userGroupIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Index')
            ->has('user_groups'));

    $userGroupCreate = $this->get(route('admin.user_group.create'));
    $userGroupCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Form')
            ->has('institutions'));

    $this->post(route('admin.user_group.store'), [
        'institution_id' => $institution->id,
        'title' => translatable('Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    $userGroup = UserGroup::query()->where('institution_id', $institution->id)->firstOrFail();

    $userGroupEdit = $this->get(route('admin.user_group.edit', ['id' => $userGroup->id]));
    $userGroupEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Form')
            ->where('user_group.id', $userGroup->id));

    $this->post(route('admin.user_group.update'), [
        'id' => $userGroup->id,
        'title' => translatable('Updated Researchers'),
    ])->assertRedirect(route('admin.user_group.index'));

    expect($userGroup->fresh()->getTranslation('title', 'en'))->toBe('Updated Researchers');

    $importForm = $this->get(route('admin.user_group.import', ['id' => $userGroup->id]));
    $importForm->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Import')
            ->where('user_group.id', $userGroup->id));

    $this->post(route('admin.user_group.users.import'), [
        'id' => $userGroup->id,
        'users' => [
            ['name' => 'imported.user'],
            ['name' => 'another.user'],
        ],
        'valid_from_date' => '2026-06-01',
        'valid_until_date' => '2026-06-30',
    ])->assertRedirect(route('admin.user_group.index'));

    $userGroupUsers = $this->get(route('admin.user_group.users', ['id' => $userGroup->id]));
    $userGroupUsers->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserGroups/Users')
            ->where('user_group.id', $userGroup->id)
            ->has('users', 2));

    $importedUsers = $userGroup->fresh()->users()->pluck('users.id')->all();
    $this->post(route('admin.user_group.users.remove'), [
        'id' => $userGroup->id,
        'users' => $importedUsers,
    ])->assertRedirect(route('admin.user_group.users', ['id' => $userGroup->id]));

    expect($userGroup->fresh()->users()->count())->toBe(0);

    $this->post(route('admin.user.delete'), ['id' => $managedUser->id])
        ->assertRedirect(route('admin.user.index'));
    expect(User::query()->find($managedUser->id))->toBeNull();

    $this->post(route('admin.user_group.delete'), ['id' => $userGroup->id])
        ->assertRedirect(route('admin.user_group.index'));
    expect(UserGroup::query()->find($userGroup->id))->toBeNull();

    $this->post(route('admin.role.delete'), ['id' => $role->id])
        ->assertRedirect(route('admin.role.index'));
    expect(Role::query()->find($role->id))->toBeNull();

    $controller = new AdminUserController(app(UserAdminService::class));
    expect($controller->getFormUsers()->pluck('id'))->toContain(auth()->id());
});

test('admin mails and happenings routes preserve their payloads and mutations', function () {
    actingAdmin();
    config()->set('broadcasting.default', 'log');

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create([
        'is_system_user' => true,
    ]);
    $verifier = User::factory()->create([
        'is_system_user' => true,
    ]);
    $mailType = MailType::query()->firstOrFail();

    $mailIndex = $this->get(route('admin.mail.index', ['institution_id' => $institution->id]));
    $mailIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Index')
            ->where('institution.id', $institution->id)
            ->has('mails'));

    $mailCreate = $this->get(route('admin.mail.create', ['institution_id' => $institution->id]));
    $mailCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Form')
            ->where('institution_id', $institution->id)
            ->has('mail_types'));

    $this->post(route('admin.mail.store'), [
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => translatable('Reservation update'),
        'title' => translatable('Mail title'),
        'salutation' => translatable('Hello'),
        'intro' => translatable('Intro'),
        'outro' => translatable('Outro'),
        'farewell' => translatable('Bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    $mail = MailContent::query()->where('institution_id', $institution->id)->firstOrFail();

    $mailEdit = $this->get(route('admin.mail.edit', ['id' => $mail->id]));
    $mailEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Form')
            ->where('mail.id', $mail->id)
            ->where('institution_id', $institution->id));

    $this->post(route('admin.mail.update'), [
        'id' => $mail->id,
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => translatable('Updated reservation update'),
        'title' => translatable('Updated mail title'),
        'salutation' => translatable('Hi'),
        'intro' => translatable('Updated intro'),
        'outro' => translatable('Updated outro'),
        'farewell' => translatable('Updated bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    expect($mail->fresh()->getTranslation('subject', 'en'))->toBe('Updated reservation update');

    $happeningsIndex = $this->get(route('admin.happening.index'));
    $happeningsIndex->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Index')
            ->has('happenings'));

    $happeningsCreate = $this->get(route('admin.happening.create'));
    $happeningsCreate->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Form')
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.store'), [
        'start_date' => '04.06.2026',
        'start_time' => '10:00',
        'end_date' => '04.06.2026',
        'end_time' => '12:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => translatable('Focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    $happening = \App\Models\Happening::query()->where('resource_id', $resource->id)->firstOrFail();

    $happeningEdit = $this->get(route('admin.happening.edit', ['id' => $happening->id]));
    $happeningEdit->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Form')
            ->where('happening.id', $happening->id)
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.update'), [
        'id' => $happening->id,
        'start_date' => '04.06.2026',
        'start_time' => '11:00',
        'end_date' => '04.06.2026',
        'end_time' => '13:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => translatable('Updated focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    expect($happening->fresh()->getTranslation('label', 'en'))->toBe('Updated focus session');

    $this->post(route('admin.happening.delete'), ['id' => $happening->id])
        ->assertRedirect(route('admin.happening.index'));
    expect(\App\Models\Happening::query()->find($happening->id))->toBeNull();

    $this->post(route('admin.mail.delete'), ['id' => $mail->id])
        ->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));
    expect(MailContent::query()->find($mail->id))->toBeNull();
});
