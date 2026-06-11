<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(AdminController::class);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(PermissionSeeder::class));

test('admin dashboard returns 200 for user with admin permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'view_users');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('admin dashboard redirects unauthenticated guest', function (): void {
    $this->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('admin dashboard returns 403 for user without admin permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});
