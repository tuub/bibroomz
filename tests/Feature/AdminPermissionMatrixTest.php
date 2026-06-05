<?php

covers(
    App\Http\Requests\Admin\AdminRouteRequest::class,
    App\Policies\InstitutionPolicy::class,
    App\Policies\ResourcePolicy::class,
    App\Policies\ResourceGroupPolicy::class,
    App\Policies\ClosingPolicy::class,
    App\Policies\SettingPolicy::class,
    App\Policies\HappeningPolicy::class
);

use App\Library\Utility;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(WeekDaySeeder::class);
    $this->seed(MailTypeSeeder::class);
});

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActor(Institution $institution): User
{
    $actor = User::factory()->create();
    // grant 'view_users' so the actor passes the 'view-admin-panel' gate
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

test('scoped admin without edit_institutions cannot update institution', function () {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.institution.update'), [
            'id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized Update'),
            'short_title' => 'UU',
            'slug' => 'unauthorized-update',
            'location' => 'Berlin',
            'week_days' => [],
            'home_uri' => 'https://example.org',
            'logo_uri' => 'https://example.org/logo.png',
            'teaser_uri' => 'https://example.org/teaser.png',
            'email' => 'info@example.org',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('institutions', ['slug' => 'unauthorized-update']);
});

test('scoped admin without delete_institutions cannot delete institution', function () {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.institution.delete'), ['id' => $institution->id])
        ->assertForbidden();

    $this->assertDatabaseHas('institutions', ['id' => $institution->id]);
});

test('scoped admin without create_resources cannot store resource', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.store'), [
            'resource_group_id' => $resourceGroup->id,
            'title' => Utility::getTranslatable('Desk X'),
            'location' => Utility::getTranslatable('Floor 1'),
            'location_uri' => 'https://example.org/map',
            'description' => Utility::getTranslatable('A desk'),
            'capacity' => 1,
            'is_active' => true,
            'is_verification_required' => false,
            'business_hours' => [[
                'id' => (string) Str::uuid(),
                'start' => '08:00',
                'end' => '18:00',
                'week_days' => [],
                'start_date' => null,
                'end_date' => null,
            ]],
        ])
        ->assertForbidden();
});

test('scoped admin without edit_happenings cannot update admin happening', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $actor = buildScopedActor($institution);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    $this->actingAs($actor)
        ->post(route('admin.happening.update'), [
            'id' => $happening->id,
            'resource_id' => $resource->id,
            'user_id_01' => $owner->id,
            'start' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'end' => now()->addHours(3)->format('Y-m-d H:i:s'),
            'label' => ['en' => 'Unauthorized update'],
        ])
        ->assertForbidden();

    expect($happening->fresh()->getTranslations('label')['en'])->toBe('Original');
});

test('scoped admin without delete_happenings cannot delete admin happening', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $owner = User::factory()->create();
    $actor = buildScopedActor($institution);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    $this->actingAs($actor)
        ->post(route('admin.happening.delete'), ['id' => $happening->id])
        ->assertForbidden();

    $this->assertDatabaseHas('happenings', ['id' => $happening->id]);
});

test('scoped admin without create_closings cannot store closing', function () {
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

test('scoped admin without create_mails cannot store mail', function () {
    $institution = Institution::factory()->create();
    $mailType = \App\Models\MailType::query()->firstOrFail();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.mail.store'), [
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType->id,
            'subject' => Utility::getTranslatable('Unauthorized mail'),
            'title' => Utility::getTranslatable('Title'),
            'salutation' => Utility::getTranslatable('Hello'),
            'intro' => Utility::getTranslatable('Intro'),
            'outro' => Utility::getTranslatable('Outro'),
            'farewell' => Utility::getTranslatable('Bye'),
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('mail_contents', ['institution_id' => $institution->id]);
});

test('scoped admin without edit_users cannot ban another user', function () {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.user.ban'), ['id' => $target->id])
        ->assertForbidden();

    expect($target->fresh()->isBanned())->toBeFalse();
});

test('scoped admin without edit_resources cannot update resource', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.update'), [
            'id' => $resource->id,
            'resource_group_id' => $resourceGroup->id,
            'title' => Utility::getTranslatable('Unauthorized Update'),
            'location' => Utility::getTranslatable('Floor 1'),
            'location_uri' => 'https://example.org/map',
            'description' => Utility::getTranslatable('A desk'),
            'capacity' => 1,
            'is_active' => true,
            'is_verification_required' => false,
            'business_hours' => [],
        ])
        ->assertForbidden();
});

test('scoped admin without delete_resources cannot delete resource', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.delete'), ['id' => $resource->id])
        ->assertForbidden();

    $this->assertDatabaseHas('resources', ['id' => $resource->id]);
});

test('scoped admin without edit_resource_groups cannot update resource group', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource_group.update'), [
            'id' => $resourceGroup->id,
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized'),
            'slug' => 'unauthorized-slug',
            'term_singular' => Utility::getTranslatable('Room'),
            'term_plural' => Utility::getTranslatable('Rooms'),
            'description' => Utility::getTranslatable('Unauthorized update'),
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('resource_groups', ['slug' => 'unauthorized-slug']);
});

test('scoped admin without delete_resource_groups cannot delete resource group', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource_group.delete'), ['id' => $resourceGroup->id])
        ->assertForbidden();

    $this->assertDatabaseHas('resource_groups', ['id' => $resourceGroup->id]);
});

test('scoped admin without edit_settings cannot update setting', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $setting = $resourceGroup->settings()->firstOrFail();
    $originalValue = $setting->value;
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.setting.update'), [
            'id' => $setting->id,
            'key' => $setting->key,
            'value' => 'unauthorized-value',
            'settingable_id' => $resourceGroup->id,
            'settingable_type' => 'resource_group',
        ])
        ->assertForbidden();

    expect($setting->fresh()->value)->toBe($originalValue);
});

test('scoped admin without create_user_groups cannot store user group', function () {
    $institution = Institution::factory()->create();
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.store'), [
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Blocked Group'),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('user_groups', ['institution_id' => $institution->id]);
});

test('scoped admin without edit_user_groups cannot update user group', function () {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Existing Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.update'), [
            'id' => $userGroup->id,
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized Rename'),
        ])
        ->assertForbidden();

    expect($userGroup->fresh()->getTranslations('title')['en'])->toBe('Existing Group');
});

test('scoped admin without delete_user_groups cannot delete user group', function () {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $institution->id,
    ]);
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.user_group.delete'), ['id' => $userGroup->id])
        ->assertForbidden();

    $this->assertDatabaseHas('user_groups', ['id' => $userGroup->id]);
});

test('scoped admin without delete_closings cannot delete closing', function () {
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

test('scoped admin without edit_mails cannot update mail', function () {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Original subject'),
        'title' => Utility::getTranslatable('Original title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $actor = buildScopedActor($institution);

    $this->actingAs($actor)
        ->post(route('admin.mail.update'), [
            'id' => $mail->id,
            'institution_id' => $institution->id,
            'mail_type_id' => $mailType->id,
            'subject' => Utility::getTranslatable('Unauthorized subject'),
            'title' => Utility::getTranslatable('Unauthorized title'),
            'salutation' => Utility::getTranslatable('Hi'),
            'intro' => Utility::getTranslatable('Intro'),
            'outro' => Utility::getTranslatable('Outro'),
            'farewell' => Utility::getTranslatable('Bye'),
            'is_active' => true,
        ])
        ->assertForbidden();

    expect($mail->fresh()->getTranslations('subject')['en'])->toBe('Original subject');
});
