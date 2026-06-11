<?php

declare(strict_types=1);

use App\Http\Requests\Admin\InstitutionRequest;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(InstitutionRequest::class);

uses(RefreshDatabase::class);

test('InstitutionRequest defines validation rules', function (): void {
    $request = new InstitutionRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('nullable')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:institutions,id')
        ->and($rules)->toHaveKey('short_title')
        ->and($rules['short_title'])->toContain('required')
        ->and($rules)->toHaveKey('slug')
        ->and($rules['slug'])->toContain('required')
        ->and($rules)->toHaveKey('is_active')
        ->and($rules['is_active'])->toContain('required')
        ->and($rules['is_active'])->toContain('boolean')
        ->and($rules)->toHaveKey('title')
        ->and($rules)->toHaveKey('home_uri')
        ->and($rules['home_uri'])->toContain('url')
        ->and($rules)->toHaveKey('logo_uri')
        ->and($rules['logo_uri'])->toContain('url')
        ->and($rules)->toHaveKey('teaser_uri')
        ->and($rules['teaser_uri'])->toContain('url')
        ->and($rules)->toHaveKey('email')
        ->and($rules['email'])->toContain('email');
});

test('InstitutionRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new InstitutionRequest;

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when no user', function (): void {
    $request = buildFormRequest(InstitutionRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can create institution', function (): void {
    // RemoveEarlyReturn would remove the early return and fall through to can('update').
    // With no institution in the request, the update check would fail. Admin creation should succeed.
    $this->seed(PermissionSeeder::class);
    $actorInstitution = Institution::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $actorInstitution, 'create_institutions');
    $request = buildFormRequest(InstitutionRequest::class, [], $user);

    // No institution_id → institutionOrNull() returns null → check can('create', Institution::class)
    expect($request->authorize())->toBeTrue();
});

test('rules contains short_title key', function (): void {
    $rules = (new InstitutionRequest)->rules();

    expect($rules)->toHaveKey('short_title');
    expect($rules['short_title'])->toContain('required');
});

test('rules contains slug key', function (): void {
    $rules = (new InstitutionRequest)->rules();

    expect($rules)->toHaveKey('slug');
    expect($rules['slug'])->toContain('required');
});

test('authorize returns false when user cannot edit existing institution', function (): void {
    // With an institution in the request, a non-admin user without edit permissions should get false
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(InstitutionRequest::class, ['id' => $institution->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when the user has the edit permission for the existing institution', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'edit_institutions');
    $request = buildFormRequest(InstitutionRequest::class, ['id' => $institution->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize does not treat create permission as update permission for an existing institution', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $actorInstitution = Institution::factory()->create();
    $user = User::factory()->create();
    grantAdminPermission($user, $actorInstitution, 'create_institutions');

    $request = buildFormRequest(InstitutionRequest::class, ['id' => $institution->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('slug rules include the unique rule and location keeps the exact empty array rule', function (): void {
    $rules = (new InstitutionRequest)->rules();

    expect($rules['location'])->toBe([]);
    expect($rules['slug'])->toHaveCount(2);
});
