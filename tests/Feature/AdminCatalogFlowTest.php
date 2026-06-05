<?php

covers(
    App\Http\Controllers\Admin\InstitutionController::class,
    App\Http\Controllers\Admin\ResourceController::class,
    App\Http\Controllers\Admin\ResourceGroupController::class,
    App\Http\Controllers\Admin\ClosingController::class,
    App\Http\Controllers\Admin\MailController::class,
    App\Http\Controllers\Admin\SettingController::class,
    App\Services\Admin\InstitutionAdminService::class,
    App\Services\Admin\ResourceAdminService::class,
    App\Services\Admin\ResourceGroupAdminService::class,
    App\Services\Admin\ClosingAdminService::class,
    App\Services\Admin\MailAdminService::class,
    App\Services\Admin\SettingAdminService::class,
    App\Services\Admin\SettingableResolver::class,
    App\Services\Admin\ClosableResolver::class,
    App\Http\Requests\Admin\InstitutionRequest::class,
    App\Http\Requests\Admin\ResourceGroupRequest::class,
    App\Http\Requests\Admin\StoreResourceRequest::class,
    App\Http\Requests\Admin\UpdateResourceRequest::class,
    App\Http\Requests\Admin\StoreClosingRequest::class,
    App\Http\Requests\Admin\UpdateClosingRequest::class,
    App\Http\Requests\Admin\MailContentRequest::class,
    App\Http\Requests\Admin\UpdateSettingRequest::class
);

use App\Library\Utility;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\WeekDay;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
    config()->set('broadcasting.default', 'log');
});

function adminCatalogFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param list<string> $permissions
 */
function actingCatalogFeatureAdmin(Institution $institution, array $permissions): User
{
    $user = User::factory()->create([
        'is_system_user' => true,
        'is_admin' => false,
    ]);

    foreach ($permissions as $permission) {
        grantAdminPermission($user, $institution, $permission);
    }

    test()->actingAs($user);

    return $user;
}

test('guests are redirected away from admin routes', function () {
    $this->get('/admin')->assertRedirect(route('start'));
});

test('scoped admins without catalog create permission cannot store institutions', function () {
    $scopeInstitution = Institution::factory()->create();
    actingCatalogFeatureAdmin($scopeInstitution, ['view_institutions']);

    $weekDayIds = WeekDay::query()->pluck('id')->all();

    $this->post(route('admin.institution.store'), [
        'title' => adminCatalogFeatureTranslatable('Blocked Institution'),
        'short_title' => 'BI',
        'slug' => 'blocked-institution',
        'location' => 'Berlin',
        'week_days' => $weekDayIds,
        'home_uri' => 'https://example.org',
        'logo_uri' => 'https://example.org/logo.png',
        'teaser_uri' => 'https://example.org/teaser.png',
        'email' => 'info@example.org',
        'is_active' => true,
    ])->assertForbidden();

    $this->assertDatabaseMissing('institutions', ['slug' => 'blocked-institution']);
});

test('catalog admin routes render and mutate institutions resources settings closings and mails', function () {
    $scopeInstitution = Institution::factory()->create();
    actingCatalogFeatureAdmin($scopeInstitution, [
        'view_institutions',
        'create_institutions',
        'edit_institutions',
        'delete_institutions',
        'view_resource_groups',
        'create_resource_groups',
        'edit_resource_groups',
        'delete_resource_groups',
        'view_resources',
        'create_resources',
        'edit_resources',
        'delete_resources',
        'view_settings',
        'edit_settings',
        'view_closings',
        'create_closings',
        'edit_closings',
        'delete_closings',
        'view_mails',
        'create_mails',
        'edit_mails',
        'delete_mails',
    ]);

    $weekDayIds = WeekDay::query()->pluck('id')->all();

    $this->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Dashboard'));

    $this->get(route('admin.institution.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Index')
            ->has('institutions'));

    $this->get(route('admin.institution.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Form')
            ->has('daysOfWeek')
            ->has('languages'));

    $this->post(route('admin.institution.store'), [
        'title' => adminCatalogFeatureTranslatable('Feature Institution'),
        'short_title' => 'FI',
        'slug' => 'feature-institution',
        'location' => 'Berlin',
        'week_days' => $weekDayIds,
        'home_uri' => 'https://example.org',
        'logo_uri' => 'https://example.org/logo.png',
        'teaser_uri' => 'https://example.org/teaser.png',
        'email' => 'info@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    $institution = Institution::query()->where('slug', 'feature-institution')->firstOrFail();

    $currentUser = auth()->user();

    if ($currentUser instanceof User) {
        foreach (
            [
            'view_resource_groups',
            'create_resource_groups',
            'edit_resource_groups',
            'delete_resource_groups',
            'view_resources',
            'create_resources',
            'edit_resources',
            'delete_resources',
            'view_settings',
            'edit_settings',
            'view_closings',
            'create_closings',
            'edit_closings',
            'delete_closings',
            'view_mails',
            'create_mails',
            'edit_mails',
            'delete_mails',
            ] as $permission
        ) {
            grantAdminPermission($currentUser, $institution, $permission);
        }

        $currentUser->unsetRelation('roles');
        $currentUser->unsetRelation('institutions');
    }

    $this->get(route('admin.institution.edit', ['id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Institutions/Form')
            ->where('institution.id', $institution->id)
            ->has('daysOfWeek'));

    $this->post(route('admin.institution.update'), [
        'id' => $institution->id,
        'title' => adminCatalogFeatureTranslatable('Updated Feature Institution'),
        'short_title' => 'UFI',
        'slug' => 'updated-feature-institution',
        'location' => 'Potsdam',
        'week_days' => array_slice($weekDayIds, 0, 5),
        'home_uri' => 'https://example.org/home',
        'logo_uri' => 'https://example.org/logo-2.png',
        'teaser_uri' => 'https://example.org/teaser-2.png',
        'email' => 'updated@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    expect($institution->fresh()->slug)->toBe('updated-feature-institution');

    $this->post(route('admin.institution.order'), [
        ['id' => $institution->id, 'order' => 7],
    ])->assertOk();

    expect($institution->fresh()->order)->toBe(7);

    $this->get(route('admin.resource_group.index', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Index')
            ->where('institution.id', $institution->id)
            ->has('resource_groups'));

    $this->get(route('admin.resource_group.create', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Form')
            ->where('institution.id', $institution->id)
            ->has('institutions'));

    $this->post(route('admin.resource_group.store'), [
        'institution_id' => $institution->id,
        'title' => adminCatalogFeatureTranslatable('Rooms'),
        'slug' => 'rooms',
        'term_singular' => adminCatalogFeatureTranslatable('Room'),
        'term_plural' => adminCatalogFeatureTranslatable('Rooms'),
        'description' => adminCatalogFeatureTranslatable('Available rooms'),
        'is_active' => true,
        'user_groups' => [],
        'help_uri' => 'https://example.org/help',
    ])->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    $resourceGroup = ResourceGroup::query()->where('slug', 'rooms')->firstOrFail();

    $this->get(route('admin.resource_group.edit', ['id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ResourceGroups/Form')
            ->where('resource_group.id', $resourceGroup->id)
            ->has('institutions'));

    $this->post(route('admin.resource_group.update'), [
        'id' => $resourceGroup->id,
        'institution_id' => $institution->id,
        'title' => adminCatalogFeatureTranslatable('Study Rooms'),
        'slug' => 'study-rooms',
        'term_singular' => adminCatalogFeatureTranslatable('Study room'),
        'term_plural' => adminCatalogFeatureTranslatable('Study rooms'),
        'description' => adminCatalogFeatureTranslatable('Updated description'),
        'is_active' => true,
        'user_groups' => [],
        'help_uri' => 'https://example.org/help-2',
    ])->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    expect($resourceGroup->fresh()->slug)->toBe('study-rooms');

    $this->post(route('admin.resource_group.order'), [
        ['id' => $resourceGroup->id, 'order' => 4],
    ])->assertOk();

    expect($resourceGroup->fresh()->order)->toBe(4);

    $this->get(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Index')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->has('resources'));

    $this->get(route('admin.resource.create', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Form')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->has('weekDays'));

    $businessHourId = (string) Str::uuid();

    $this->post(route('admin.resource.store'), [
        'resource_group_id' => $resourceGroup->id,
        'title' => adminCatalogFeatureTranslatable('Desk A'),
        'location' => adminCatalogFeatureTranslatable('First Floor'),
        'location_uri' => 'https://example.org/map',
        'description' => adminCatalogFeatureTranslatable('Quiet desk'),
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

    $this->get(route('admin.resource.edit', ['id' => $resource->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Form')
            ->where('resource.id', $resource->id)
            ->has('resource.business_hours', 1));

    $this->post(route('admin.resource.update'), [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => adminCatalogFeatureTranslatable('Desk B'),
        'location' => adminCatalogFeatureTranslatable('Second Floor'),
        'location_uri' => 'https://example.org/map-2',
        'description' => adminCatalogFeatureTranslatable('Updated quiet desk'),
        'capacity' => 4,
        'is_active' => true,
        'is_verification_required' => true,
        'business_hours' => [[
            'id' => $resourceBusinessHour->id,
            'start' => '09:00',
            'end' => '17:00',
            'week_days' => array_slice($weekDayIds, 0, 5),
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]],
    ])->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));

    $updatedResource = $resource->fresh(['business_hours.week_days']);

    expect((int) $updatedResource->capacity)->toBe(4)
        ->and($updatedResource->is_verification_required)->toBeTrue()
        ->and($updatedResource->business_hours)->toHaveCount(1)
        ->and($updatedResource->business_hours->first()?->start)->toBe('09:00')
        ->and($updatedResource->business_hours->first()?->end)->toBe('17:00');

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

    $cloneResponse->assertRedirect(route('admin.resource.edit', $clonedResource->id));

    $this->get(route('admin.setting.index', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Index')
            ->where('settingable.id', $institution->id)
            ->where('settingable_type', 'institution')
            ->has('settings'));

    $setting = $institution->settings()->firstOrFail();

    $this->get(route('admin.setting.edit', ['id' => $setting->id]))
        ->assertOk()
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

    $this->get(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Closings/Index')
            ->where('closable.id', $institution->id)
            ->where('closable_type', 'institution'));

    $this->get(route('admin.closing.create', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]))
        ->assertOk()
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
        'description' => adminCatalogFeatureTranslatable('Morning maintenance'),
    ])->assertRedirect(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));

    $closing = Closing::query()->where('closable_id', $institution->id)->firstOrFail();

    $this->get(route('admin.closing.edit', ['id' => $closing->id]))
        ->assertOk()
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
        'description' => adminCatalogFeatureTranslatable('Shifted maintenance'),
    ])->assertRedirect(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]));

    expect($closing->fresh()->getTranslation('description', 'en'))->toBe('Shifted maintenance');

    $this->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertRedirect(route('admin.closing.index', [
            'closable_type' => 'institution',
            'closable_id' => $institution->id,
        ]));

    $this->assertSoftDeleted('closings', ['id' => $closing->id]);

    $mailType = MailType::query()->firstOrFail();

    $this->get(route('admin.mail.index', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Index')
            ->where('institution.id', $institution->id)
            ->has('mails'));

    $this->get(route('admin.mail.create', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Form')
            ->where('institution_id', $institution->id)
            ->has('mail_types'));

    $this->post(route('admin.mail.store'), [
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => adminCatalogFeatureTranslatable('Reservation update'),
        'title' => adminCatalogFeatureTranslatable('Mail title'),
        'salutation' => adminCatalogFeatureTranslatable('Hello'),
        'intro' => adminCatalogFeatureTranslatable('Intro'),
        'outro' => adminCatalogFeatureTranslatable('Outro'),
        'farewell' => adminCatalogFeatureTranslatable('Bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    $mail = MailContent::query()->where('institution_id', $institution->id)->firstOrFail();

    $this->get(route('admin.mail.edit', ['id' => $mail->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Mails/Form')
            ->where('mail.id', $mail->id)
            ->where('institution_id', $institution->id));

    $this->post(route('admin.mail.update'), [
        'id' => $mail->id,
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => adminCatalogFeatureTranslatable('Updated reservation update'),
        'title' => adminCatalogFeatureTranslatable('Updated mail title'),
        'salutation' => adminCatalogFeatureTranslatable('Hi'),
        'intro' => adminCatalogFeatureTranslatable('Updated intro'),
        'outro' => adminCatalogFeatureTranslatable('Updated outro'),
        'farewell' => adminCatalogFeatureTranslatable('Updated bye'),
        'is_active' => true,
    ])->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    expect($mail->fresh()->getTranslation('subject', 'en'))->toBe('Updated reservation update');

    $this->post(route('admin.resource.delete'), ['id' => $clonedResource->id])
        ->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));
    $this->post(route('admin.resource.delete'), ['id' => $resource->id])
        ->assertRedirect(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]));
    $this->post(route('admin.resource_group.delete'), ['id' => $resourceGroup->id])
        ->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));
    $this->post(route('admin.mail.delete'), ['id' => $mail->id])
        ->assertRedirect(route('admin.mail.index', ['institution_id' => $institution->id]));

    $deleteTarget = Institution::factory()->create();

    $this->post(route('admin.institution.delete'), ['id' => $deleteTarget->id])
        ->assertRedirect(route('admin.institution.index'));

    $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
    $this->assertDatabaseMissing('resources', ['id' => $clonedResource->id]);
    $this->assertDatabaseMissing('resource_groups', ['id' => $resourceGroup->id]);
    $this->assertDatabaseMissing('mail_contents', ['id' => $mail->id]);
    $this->assertDatabaseMissing('institutions', ['id' => $deleteTarget->id]);
});
