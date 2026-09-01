<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Requests\Admin\ImpersonateUserRequest;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

covers(ImpersonationController::class, ImpersonateUserRequest::class, UserPolicy::class);

uses(RefreshDatabase::class);

test('an admin can start impersonating another user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $target->id])
        ->assertRedirect(route('start'));

    expect(Auth::guard('web')->id())->toBe($target->id)
        ->and(session('impersonator_id'))->toBe($admin->id);
});

test('an admin can impersonate another admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $targetAdmin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $targetAdmin->id])
        ->assertRedirect(route('start'));

    expect(Auth::guard('web')->id())->toBe($targetAdmin->id);
});

test('an admin can impersonate a banned user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $bannedTarget = User::factory()->create(['is_admin' => false, 'banned_at' => now()]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $bannedTarget->id])
        ->assertRedirect(route('start'));

    expect(Auth::guard('web')->id())->toBe($bannedTarget->id);
});

test('impersonating without an id is forbidden', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), [])
        ->assertForbidden();

    expect(Auth::guard('web')->id())->toBe($admin->id);
});

test('impersonating a non-existent user id is forbidden', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => (string) Str::uuid()])
        ->assertForbidden();

    expect(Auth::guard('web')->id())->toBe($admin->id);
});

test('a non-admin cannot start impersonating another user', function (): void {
    $actor = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);

    $this->actingAs($actor)
        ->post(route('admin.user.impersonate'), ['id' => $target->id])
        ->assertForbidden();

    expect(Auth::guard('web')->id())->toBe($actor->id);
});

test('an admin cannot impersonate themselves', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $admin->id])
        ->assertForbidden();
});

test('an admin cannot start a nested impersonation session', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $firstTarget = User::factory()->create(['is_admin' => true]);
    $secondTarget = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $firstTarget->id])
        ->assertRedirect(route('start'));

    $this->post(route('admin.user.impersonate'), ['id' => $secondTarget->id])
        ->assertForbidden();

    expect(session('impersonator_id'))->toBe($admin->id)
        ->and(Auth::guard('web')->id())->toBe($firstTarget->id);
});

test('stopping impersonation restores the original admin', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $target->id])
        ->assertRedirect(route('start'));

    $this->post(route('admin.impersonate.stop'))
        ->assertRedirect(route('admin.user.index'));

    expect(Auth::guard('web')->id())->toBe($admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

test('stopping impersonation without an active session is forbidden', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.impersonate.stop'))
        ->assertForbidden();
});

test('stopping impersonation is forbidden when the stored impersonator no longer exists', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin)
        ->post(route('admin.user.impersonate'), ['id' => $target->id])
        ->assertRedirect(route('start'));

    $admin->delete();

    $this->post(route('admin.impersonate.stop'))
        ->assertForbidden();
});
