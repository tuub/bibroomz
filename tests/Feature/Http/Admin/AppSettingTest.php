<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AppSettingController;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

covers(AppSettingController::class);

uses(RefreshDatabase::class);

test('index returns 403 for non-admin user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.app_setting.index'))->assertForbidden();
});

test('edit returns 403 for non-admin user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.app_setting.edit'))->assertForbidden();
});

test('update returns 403 for non-admin user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->post(route('admin.app_setting.update'), [
        'system_notification' => 'unauthorized-value',
    ])->assertForbidden();

    expect(AppSetting::query()->count())->toBe(0);
});

test('index returns ok for admin user with the current settings', function (): void {
    AppSetting::set('system_notification', 'Existing notice');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.app_setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/AppSettings/Index')
            ->where('settings', [
                ['key' => 'system_notification', 'value' => 'Existing notice'],
            ]));
});

test('index returns the default value when no row exists yet', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.app_setting.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/AppSettings/Index')
            ->where('settings', [
                ['key' => 'system_notification', 'value' => ''],
            ]));
});

test('edit returns ok for admin user with the current settings and input types', function (): void {
    AppSetting::set('system_notification', 'Existing notice');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.app_setting.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/AppSettings/Edit')
            ->where('settings.system_notification', 'Existing notice')
            ->where('inputTypes.system_notification', 'textarea'));
});

test('edit returns the default value when no row exists yet', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.app_setting.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/AppSettings/Edit')
            ->where('settings.system_notification', ''));
});

test('update creates the row when none exists yet', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.app_setting.update'), [
        'system_notification' => 'Maintenance tonight',
    ])->assertRedirect(route('admin.app_setting.index'));

    expect(AppSetting::get('system_notification'))->toBe('Maintenance tonight');
});

test('update overwrites the existing value for a key', function (): void {
    AppSetting::set('system_notification', 'Old notice');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.app_setting.update'), [
        'system_notification' => 'New notice',
    ])->assertRedirect(route('admin.app_setting.index'));

    expect(AppSetting::query()->count())->toBe(1)
        ->and(AppSetting::get('system_notification'))->toBe('New notice');
});

test('update can clear the notification', function (): void {
    AppSetting::set('system_notification', 'Old notice');
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->post(route('admin.app_setting.update'), [
        'system_notification' => '',
    ])->assertRedirect(route('admin.app_setting.index'));

    expect(AppSetting::query()->find('system_notification')?->value)->toBeNull();
});
