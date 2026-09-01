<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ImpersonateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ImpersonateUserRequest::class);

uses(RefreshDatabase::class);

beforeEach(fn () => session()->forget('impersonator_id'));

test('ImpersonateUserRequest rules returns all required id validation rules', function (): void {
    $request = new ImpersonateUserRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:users,id');
});

test('ImpersonateUserRequest authorize returns true for an admin impersonating another user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $request = buildAdminFormRequest(ImpersonateUserRequest::class, ['id' => $target->id], $admin);

    expect($request->authorize())->toBeTrue();
});

test('ImpersonateUserRequest authorize returns false for a non-admin', function (): void {
    $actor = User::factory()->create(['is_admin' => false]);
    $target = User::factory()->create(['is_admin' => false]);

    $request = buildAdminFormRequest(ImpersonateUserRequest::class, ['id' => $target->id], $actor);

    expect($request->authorize())->toBeFalse();
});

test('ImpersonateUserRequest authorize returns false when actor is null', function (): void {
    $target = User::factory()->create();
    $request = buildFormRequest(ImpersonateUserRequest::class, ['id' => $target->id]);

    expect($request->authorize())->toBeFalse();
});

test('ImpersonateUserRequest authorize returns false when no target model found', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $request = new ImpersonateUserRequest;
    $request->setUserResolver(fn (): User => $admin);
    $request->setContainer(app());

    expect($request->authorize())->toBeFalse();
});

test('ImpersonateUserRequest authorize returns false when the target is the actor themselves', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(ImpersonateUserRequest::class, ['id' => $admin->id], $admin);

    expect($request->authorize())->toBeFalse();
});

test('ImpersonateUserRequest authorize returns false while already impersonating', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);
    session()->put('impersonator_id', (string) User::factory()->create(['is_admin' => true])->id);

    $request = buildAdminFormRequest(ImpersonateUserRequest::class, ['id' => $target->id], $admin);

    expect($request->authorize())->toBeFalse();
});

test('ImpersonateUserRequest targetUser accessor returns the correct model', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => false]);

    $request = buildAdminFormRequest(ImpersonateUserRequest::class, ['id' => $target->id], $admin);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->targetUser()->id)->toBe($target->id);
});
