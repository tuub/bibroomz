<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SettingController;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
    // RemoveMethodCall would remove $this->authorize('edit', $setting)
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_key',
        'value' => 'test_value',
    ]);
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.setting.edit', ['id' => $setting->id]))
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
    $setting = $institution->settings()->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->get(route('admin.setting.edit', ['id' => $setting->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Settings/Form')
            ->where('setting.id', $setting->id));
});

test('updateSetting returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');
    $this->actingAs($actor);

    $this->post(route('admin.setting.update'), [
        'id' => $setting->id,
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

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editSetting returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForSettings($institution);

    $this->actingAs($actor)
        ->get(route('admin.setting.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('updateSetting returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $setting = $institution->settings()->firstOrFail();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_settings');

    // Provide id to pass authorize(), but omit key/value/settingable fields
    $this->actingAs($actor)
        ->post(route('admin.setting.update'), ['id' => $setting->id])
        ->assertRedirect();
});

test('scoped admin without edit_settings cannot update setting', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $setting = $resourceGroup->settings()->firstOrFail();
    $originalValue = $setting->value;
    $actor = buildScopedActorForSettings($institution);

    $this->actingAs($actor)
        ->post(route('admin.setting.update'), [
            'id' => $setting->id,
            'key' => $setting->key,
            'value' => 'unauthorized-value',
            'settingable_id' => $resourceGroup->id,
            'settingable_type' => 'resource_group',
        ])
        ->assertForbidden();

    expect($setting->fresh()?->value)->toBe($originalValue);
});
