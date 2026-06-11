<?php

declare(strict_types=1);

use App\Http\Requests\Admin\DeleteInstitutionRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteInstitutionRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('DeleteInstitutionRequest defines validation rules', function (): void {
    $request = new DeleteInstitutionRequest;

    expect($request->rules())->toBeArray();
});

test('DeleteInstitutionRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteInstitutionRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteInstitutionRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:institutions,id');
});

test('authorize returns false when no institution found even with authorized user', function (): void {
    // InstanceOfToTrue on $institution instanceof Model would bypass the null check.
    // Must test that false is returned when institution is null.
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteInstitutionRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user is null even when institution exists', function (): void {
    // InstanceOfToTrue on $user instanceof User would bypass the null check.
    $institution = Institution::factory()->create();
    $request = buildFormRequest(DeleteInstitutionRequest::class, ['id' => $institution->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can delete institution', function (): void {
    // BooleanAndToBooleanOr would change && to ||, so we need the true path to be tested
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteInstitutionRequest::class, ['id' => $institution->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('institution accessor returns model after findModelOrFail', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteInstitutionRequest::class, ['id' => $institution->id], $user);
    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id);
});
