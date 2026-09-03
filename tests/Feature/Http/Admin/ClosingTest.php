<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ClosingController;
use App\Http\Controllers\Admin\InstitutionController;
use App\Http\Controllers\Admin\MailController;
use App\Http\Controllers\Admin\ResourceGroupController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Library\Utility;
use App\Mail\ClosingMail;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\WeekDay;
use App\Services\Admin\ClosableResolver;
use App\Services\Admin\InstitutionAdminService;
use App\Services\Admin\MailAdminService;
use App\Services\Admin\ResourceAdminService;
use App\Services\Admin\ResourceGroupAdminService;
use App\Services\Admin\SettingAdminService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

covers(
    ClosingController::class,
    InstitutionController::class,
    ResourceGroupController::class,
    MailController::class,
    SettingController::class,
    InstitutionAdminService::class,
    ResourceAdminService::class,
    ResourceGroupAdminService::class,
    MailAdminService::class,
    SettingAdminService::class,
    ClosableResolver::class,
    UpdateSettingRequest::class,
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00'));
    config()->set('broadcasting.default', 'log');
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @return array<string, string>
 */
function adminCatalogFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param  list<string>  $permissions
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

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, happening: Happening, admin: User}
 */
function buildClosingNotificationFixture(): array
{
    $institution = Institution::factory()->create([
        'is_active' => true,
        'email' => 'library@example.test',
    ]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => false,
    ]);

    $owner = User::factory()->create(['name' => 'closing.notify.owner']);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Study'),
    ]);

    $mailTypeCreated = MailType::query()->firstWhere('key', 'closing_created');
    $mailTypeUpdated = MailType::query()->firstWhere('key', 'closing_updated');
    $mailTypeDeleted = MailType::query()->firstWhere('key', 'closing_deleted');

    foreach ([$mailTypeCreated, $mailTypeUpdated, $mailTypeDeleted] as $mailType) {
        MailContent::create([
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType?->id,
            'subject' => 'Closing Notice',
            'title' => 'Library Closing',
            'salutation' => 'Dear User',
            'intro' => 'We regret to inform you...',
            'outro' => 'Best regards',
            'is_active' => true,
        ]);
    }

    $admin = User::factory()->create(['is_system_user' => true, 'is_admin' => false]);
    foreach (['view_closings', 'create_closings', 'edit_closings', 'delete_closings'] as $perm) {
        grantAdminPermission($admin, $institution, $perm);
    }
    test()->actingAs($admin);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'happening' => $happening, 'admin' => $admin];
}

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActor(Institution $institution): User
{
    $actor = User::factory()->create();
    // grant 'view_users' so the actor passes the 'view-admin-panel' gate
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From AdminClosingNotificationTest
// ---------------------------------------------------------------------------

test('creating an institution closing with an affected happening dispatches a closing mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('System maintenance'),
    ])->assertRedirect();

    Mail::assertQueued(ClosingMail::class, fn (ClosingMail $mail): bool => $mail->hasTo($fixture['owner']->email ?? ''));
});

test('creating a closing with notify_users disabled does not dispatch a closing mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Silent maintenance'),
        'notify_users' => false,
    ])->assertRedirect();

    Mail::assertNothingQueued();
});

test('updating a closing with an affected happening dispatches an update mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Initial'),
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.update'), [
        'id' => $closing->id,
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'description' => Utility::getTranslatable('Extended'),
    ])->assertRedirect();

    Mail::assertQueued(ClosingMail::class);
});

test('updating a closing with notify_users disabled does not dispatch an update mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Initial'),
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.update'), [
        'id' => $closing->id,
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'description' => Utility::getTranslatable('Extended'),
        'notify_users' => false,
    ])->assertRedirect();

    Mail::assertNothingQueued();
});

test('deleting a closing with a previously affected happening dispatches a deletion mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Temporary'),
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertRedirect();

    Mail::assertQueued(ClosingMail::class);
});

test('deleting a closing with notify_users disabled does not dispatch a deletion mail', function (): void {
    Mail::fake();
    $fixture = buildClosingNotificationFixture();

    $this->post(route('admin.closing.store'), [
        'closable_type' => 'institution',
        'closable_id' => $fixture['institution']->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:30',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'description' => Utility::getTranslatable('Temporary'),
        'notify_users' => false,
    ])->assertRedirect();

    Mail::fake();

    $closing = $fixture['institution']->closings()->firstOrFail();

    $this->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertRedirect();

    Mail::assertNothingQueued();
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editClosing returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->get(route('admin.closing.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeClosing returns redirect when required date fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_closings');

    // Provide closable_type/closable_id to pass authorize(), but omit date fields
    $this->actingAs($actor)
        ->post(route('admin.closing.store'), [
            'closable_type' => 'institution',
            'closable_id' => $institution->id,
        ])
        ->assertRedirect();
});

test('updateClosing returns redirect when required date fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Existing'),
    ]);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_closings');

    // Provide id (valid closing) to pass authorize(), but omit date fields
    $this->actingAs($actor)
        ->post(route('admin.closing.update'), ['id' => $closing->id])
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — closing tests
// ---------------------------------------------------------------------------

test('scoped admin without create_closings cannot store closing', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.closing.store'), [
            'closable_type' => 'institution',
            'closable_id' => $institution->id,
            'start_date' => '03.06.2026',
            'start_time' => '08:00',
            'end_date' => '03.06.2026',
            'end_time' => '10:00',
            'description' => Utility::getTranslatable('Maintenance'),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('closings', ['closable_id' => $institution->id]);
});

test('scoped admin without delete_closings cannot delete closing', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Maintenance window'),
    ]);
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertForbidden();

    $this->assertDatabaseHas('closings', ['id' => $closing->id]);
});

test('scoped admin without view_closings cannot view closings index', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->get(route('admin.closing.index', ['closable_type' => 'institution', 'closable_id' => $institution->id]))
        ->assertForbidden();
});

test('scoped admin without create_closings cannot view closing create form', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->get(route('admin.closing.create', ['closable_type' => 'institution', 'closable_id' => $institution->id]))
        ->assertForbidden();
});

test('scoped admin without edit_closings cannot update closing', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Maintenance window'),
    ]);
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.closing.update'), [
            'id' => $closing->id,
            'start_date' => '04.06.2026',
            'start_time' => '08:00',
            'end_date' => '04.06.2026',
            'end_time' => '10:00',
            'description' => Utility::getTranslatable('Updated description'),
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// From AdminCatalogFlowTest — the big catalog flow test
// ---------------------------------------------------------------------------

test('guests are redirected away from admin routes', function (): void {
    $this->get('/admin')->assertRedirect(route('start'));
});

test('scoped admins without catalog create permission cannot store institutions', function (): void {
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

test('catalog admin routes render and mutate institutions resources settings closings and mails', function (): void {
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
        ->assertInertia(fn (Assert $page): AssertableInertia => $page->component('Admin/Dashboard'));

    $this->get(route('admin.institution.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Institutions/Index')
            ->has('institutions'));

    $this->get(route('admin.institution.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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

    expect($institution->fresh()?->slug)->toBe('updated-feature-institution');

    $this->post(route('admin.institution.order'), [
        'rows' => [['id' => $institution->id, 'order' => 7]],
    ])->assertOk();

    expect($institution->fresh()?->order)->toBe(7);

    $this->get(route('admin.resource_group.index', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/ResourceGroups/Index')
            ->where('institution.id', $institution->id)
            ->has('resource_groups'));

    $this->get(route('admin.resource_group.create', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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

    expect($resourceGroup->fresh()?->slug)->toBe('study-rooms');

    $this->post(route('admin.resource_group.order'), [
        'rows' => [['id' => $resourceGroup->id, 'order' => 4]],
    ])->assertOk();

    expect($resourceGroup->fresh()?->order)->toBe(4);

    $this->get(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Resources/Index')
            ->where('resourceGroup.id', $resourceGroup->id)
            ->has('resources'));

    $this->get(route('admin.resource.create', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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

    expect((int) $updatedResource?->capacity)->toBe(4)
        ->and($updatedResource?->is_verification_required)->toBeTrue()
        ->and($updatedResource?->business_hours)->toHaveCount(1)
        ->and($updatedResource?->business_hours->first()?->start)->toBe('09:00')
        ->and($updatedResource?->business_hours->first()?->end)->toBe('17:00');

    $this->post(route('admin.resource.order'), [
        'rows' => [['id' => $resource->id, 'order' => 9]],
    ])->assertOk();

    expect($resource->fresh()?->order)->toBe(9);

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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Index')
            ->where('settingable.id', $institution->id)
            ->where('settingable_type', 'institution')
            ->has('settings'));

    $setting = $institution->settings()->firstOrFail();

    $this->get(route('admin.setting.edit', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
        'key' => $setting->key,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Form')
            ->where('setting.key', $setting->key)
            ->where('settingable_type', 'institution'));

    $this->post(route('admin.setting.update', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
        'key' => $setting->key,
    ]), [
        'key' => $setting->key,
        'value' => 'Europe/Paris',
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ])->assertRedirect(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]));

    expect($setting->fresh()?->value)->toBe('Europe/Paris');

    $this->get(route('admin.closing.index', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Closings/Index')
            ->where('closable.id', $institution->id)
            ->where('closable_type', 'institution'));

    $this->get(route('admin.closing.create', [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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

    expect($closing->fresh()?->getTranslation('description', 'en'))->toBe('Shifted maintenance');

    $this->post(route('admin.closing.delete'), ['id' => $closing->id])
        ->assertRedirect(route('admin.closing.index', [
            'closable_type' => 'institution',
            'closable_id' => $institution->id,
        ]));

    $this->assertSoftDeleted('closings', ['id' => $closing->id]);

    $mailType = MailType::query()->firstOrFail();

    $this->get(route('admin.mail.index', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Mails/Index')
            ->where('institution.id', $institution->id)
            ->has('mails'));

    $this->get(route('admin.mail.create', ['institution_id' => $institution->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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
        ->assertInertia(fn (Assert $page): AssertableJson => $page
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

    expect($mail->fresh()?->getTranslation('subject', 'en'))->toBe('Updated reservation update');

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
