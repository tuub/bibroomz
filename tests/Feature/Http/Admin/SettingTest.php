<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SettingController;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;

covers(SettingController::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForSettings(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/SettingControllerTest
// ---------------------------------------------------------------------------

test('getSettings returns 403 for user without view_settings permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('viewAny', [Setting::class, $settingable])
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]))->assertForbidden();
});

test('editSetting returns 403 for user without edit_settings permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('editAny', [Setting::class, $settingable])
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.setting.edit', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
        'key' => 'timezone',
    ]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — setting test
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('editSetting returns ok for authorized user', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->get(route('admin.setting.edit', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
        'key' => 'timezone',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Form')
            ->where('setting.key', 'timezone')
            ->where('input_type', 'text'));
});

test('getSettings returns ok for user with edit_settings permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->get(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Index'));
});

test('editSetting exposes textarea input type for system notification', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->get(route('admin.setting.edit', [
        'settingable_type' => 'institution',
        'settingable_id' => $institution->id,
        'key' => 'system_notification',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Form')
            ->where('setting.key', 'system_notification')
            ->where('input_type', 'textarea'));
});

test('updateSetting returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->where('key', 'timezone')->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->post(route('admin.setting.update', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
        'key' => $setting->key,
    ]), [
        'key' => $setting->key,
        'value' => 'Europe/London',
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ])->assertRedirect(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]));

    expect($setting->fresh()?->value)->toBe('Europe/London');
});

test('system notification setting can be cleared', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->where('key', 'system_notification')->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->post(route('admin.setting.update', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
        'key' => $setting->key,
    ]), [
        'key' => $setting->key,
        'value' => '',
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ])->assertRedirect(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]));

    expect($setting->fresh()?->value)->toBeNull();
});

// ---------------------------------------------------------------------------
// Definition-backed edit flows
// ---------------------------------------------------------------------------

test('editSetting returns form data even when the setting row is missing', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'system_notification')->delete();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');

    $this->actingAs($actor)
        ->get(route('admin.setting.edit', [
            'settingable_type' => 'institution',
            'settingable_id' => $institution->id,
            'key' => 'system_notification',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Form')
            ->where('setting.id', null)
            ->where('setting.key', 'system_notification')
            ->where('setting.value', '')
            ->where('input_type', 'textarea'));
});

test('editSetting returns 404 for unknown setting key', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');

    $this->actingAs($actor)
        ->get(route('admin.setting.edit', [
            'settingable_type' => 'institution',
            'settingable_id' => $institution->id,
            'key' => 'unknown_key',
        ]))
        ->assertNotFound();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('updateSetting returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');

    // Route params provide the setting identity, but the value is still required.
    $this->actingAs($actor)
        ->post(route('admin.setting.update', [
            'settingable_type' => 'institution',
            'settingable_id' => $institution->id,
            'key' => $setting->key,
        ]), [])
        ->assertRedirect();
});

test('updateSetting creates missing rows for known setting definitions', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'system_notification')->delete();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');

    $this->actingAs($actor)
        ->post(route('admin.setting.update', [
            'settingable_type' => 'institution',
            'settingable_id' => $institution->id,
            'key' => 'system_notification',
        ]), [
            'value' => 'Outage banner',
        ])
        ->assertRedirect(route('admin.setting.index', [
            'settingable_id' => $institution->id,
            'settingable_type' => 'institution',
        ]));

    expect($institution->settings()->where('key', 'system_notification')->first()?->value)->toBe('Outage banner');
});

test('getSettings returns all defined settings even when one row is missing', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'timezone')->delete();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'view_settings');
    $this->actingAs($actor);

    $this->get(route('admin.setting.index', [
        'settingable_id' => $institution->id,
        'settingable_type' => 'institution',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Index')
            ->has('settings', count(Setting::getDefinitionKeys('institution'))));
});

test('scoped admin without edit_settings cannot update setting', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $setting = $resourceGroup->settings()->firstOrFail();
    $originalValue = $setting->value;
    $actor = buildScopedActorForSettings($institution);

    $this->actingAs($actor)
        ->post(route('admin.setting.update', [
            'settingable_type' => 'resource_group',
            'settingable_id' => $resourceGroup->id,
            'key' => $setting->key,
        ]), [
            'key' => $setting->key,
            'value' => 'unauthorized-value',
            'settingable_id' => $resourceGroup->id,
            'settingable_type' => 'resource_group',
        ])
        ->assertForbidden();

    expect($setting->fresh()?->value)->toBe($originalValue);
});
