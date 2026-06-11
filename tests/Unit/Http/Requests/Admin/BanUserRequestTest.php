<?php

declare(strict_types=1);

use App\Http\Requests\Admin\BanUserRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(BanUserRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('BanUserRequest rules returns all required id validation rules', function (): void {
    $request = new BanUserRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:users,id');
});

test('BanUserRequest authorize returns false for non-admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new BanUserRequest;

    expect($request->authorize())->toBeFalse();
});

test('BanUserRequest authorize returns false when no target model found', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);
    $request = new BanUserRequest;

    expect($request->authorize())->toBeFalse();
});

test('BanUserRequest authorize returns true when user has ban permission', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(BanUserRequest::class, ['id' => $target->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('BanUserRequest authorize returns false when actor is null even with target', function (): void {
    $target = User::factory()->create();
    $request = buildFormRequest(BanUserRequest::class, ['id' => $target->id]);

    expect($request->authorize())->toBeFalse();
});

test('BanUserRequest targetUser accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $target = User::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_users');

    $request = buildAdminFormRequest(BanUserRequest::class, ['id' => $target->id], $user);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->targetUser()->id)->toBe($target->id);
});
