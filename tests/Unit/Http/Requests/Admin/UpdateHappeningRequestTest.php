<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(UpdateHappeningRequest::class);

uses(RefreshDatabase::class);

test('UpdateHappeningRequest defines validation rules', function (): void {
    $request = new UpdateHappeningRequest;

    expect($request->rules())->toBeArray();
});

test('UpdateHappeningRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new UpdateHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('start_date')
        ->toHaveKey('start_time')
        ->toHaveKey('end_date')
        ->toHaveKey('end_time')
        ->toHaveKey('resource_id')
        ->toHaveKey('user_id_01')
        ->toHaveKey('user_id_02')
        ->toHaveKey('verifier')
        ->toHaveKey('is_verified')
        ->toHaveKey('label');
});

test('id field rules contain sometimes nullable uuid exists happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['id'])
        ->toContain('sometimes')
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:happenings,id');
});

test('start_date field rules contain required and date_format:d.m.Y', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['start_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('start_time field rules contain required and date_format:H:i', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['start_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('end_date field rules contain required and date_format:d.m.Y', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['end_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('end_time field rules contain required and date_format:H:i', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['end_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('resource_id field rules contain required uuid exists resources', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['resource_id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resources,id');
});

test('user_id_01 field rules contain required uuid exists users', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_01'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:users,id');
});

test('user_id_02 field rules contain sometimes nullable uuid exists users', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_02'])
        ->toContain('sometimes')
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:users,id');
});

test('is_verified field rules contain required and boolean', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['is_verified'])
        ->toContain('required')
        ->toContain('boolean');
});

test('start_date rejects Y-m-d format', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $v = Validator::make([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '2026-06-10',
        'start_time' => '10:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'is_verified' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('start_date'))->toBeTrue();
});

test('happeningOrNull returns null when no id is provided', function (): void {
    $request = buildFormRequest(UpdateHappeningRequest::class, []);

    expect($request->happeningOrNull())->toBeNull();
});

test('authorize returns false when happening is null but user and resource are both valid', function (): void {
    // BooleanOrToBooleanAnd changes || to && — meaning authorize() only returns false
    // if ALL three conditions are invalid simultaneously. This test has user+resource valid
    // but happening=null, which should still return false.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_verification_required' => false]);
    $user = User::factory()->create(['is_admin' => true]);

    // No 'id' provided → happening is null
    $request = buildFormRequest(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource is null but user and happening are both valid', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_verification_required' => false]);
    $user = User::factory()->create(['is_admin' => true]);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // Provide happening id but no resource_id → resource() = null
    $request = buildFormRequest(UpdateHappeningRequest::class, [
        'id' => $happening->id,
        'user_id_01' => $user->id,
        // no resource_id
    ], $user);

    expect($request->authorize())->toBeFalse();
});

// --- Mutation-killing tests for authorize() ---

test('authorize returns false when user is null', function (): void {
    // No user → authorize must return false regardless of happening/resource
    $request = new UpdateHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when happening is null', function (): void {
    // User set but no happening_id → happeningOrNull() returns null
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(UpdateHappeningRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource is null', function (): void {
    // user + happening but no resource_id → resource() returns null
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // Provide happening id but no resource_id → resource() = null
    $request = buildFormRequest(UpdateHappeningRequest::class, [
        'id' => $happening->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

// --- Mutation-killing test for authorize() (RemoveArrayItem) ---
// return $user->can('adminCreate', [Happening::class, $resource->resource_group->institution])
// The RemoveArrayItem mutation removes one item from the array argument.
// This path is triggered when resource_group_id differs between happening's resource and new resource.

test('happeningOrNull returns happening when id is provided', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $request = buildFormRequest(UpdateHappeningRequest::class, [
        'id' => $happening->id,
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ]);

    expect($request->happeningOrNull())->not->toBeNull()
        ->and($request->happeningOrNull()?->id)->toBe($happening->id);
});
