<?php

use App\Http\Requests\Admin\DeleteClosingRequest;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteClosingRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $request = buildFormRequest(DeleteClosingRequest::class, ['id' => $closing->id]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when closing not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(DeleteClosingRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target closing', function (): void {
    $request = buildAdminFormRequest(DeleteClosingRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when permissioned user has no target closing', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_closings');

    $request = buildAdminFormRequest(DeleteClosingRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks delete_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(DeleteClosingRequest::class, ['id' => $closing->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has delete_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_closings');

    $request = buildAdminFormRequest(DeleteClosingRequest::class, ['id' => $closing->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('closing accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_closings');

    $request = buildAdminFormRequest(DeleteClosingRequest::class, ['id' => $closing->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closing()->id)->toBe($closing->id);
});

test('closing accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_closings');

    $request = buildAdminFormRequest(DeleteClosingRequest::class, ['id' => $closing->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $closing->forceDelete();

    expect(fn () => $request->closing())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteClosingRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:closings,id');
});
