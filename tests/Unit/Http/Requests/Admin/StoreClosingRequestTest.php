<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreClosingRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(StoreClosingRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include all required closing fields', function (): void {
    $request = buildFormRequest(StoreClosingRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('closable_id')
        ->and($rules)->toHaveKey('closable_type')
        ->and($rules)->toHaveKey('start_date')
        ->and($rules)->toHaveKey('start_time')
        ->and($rules)->toHaveKey('end_date')
        ->and($rules)->toHaveKey('end_time')
        ->and($rules)->toHaveKey('description');
});

test('start_date requires d.m.Y format', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
    ]);
    $rules = $request->rules();

    $validator = Validator::make([
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
        'start_date' => '2026-06-10',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_date'))->toBeTrue();
});

test('end_time requires H:i format', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
    ]);
    $rules = $request->rules();

    $validator = Validator::make([
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('end_time'))->toBeTrue();
});

test('validation passes with valid closing data', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
    ]);
    $rules = $request->rules();

    $validator = Validator::make([
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when closable cannot be resolved', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks create_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has create_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_closings');

    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('closable returns institution model for institution type', function (): void {
    $institution = Institution::factory()->create();
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ]);

    $closable = $request->closable();

    expect($closable)->toBeInstanceOf(Institution::class)
        ->and($closable?->id)->toBe($institution->id);
});

test('closable returns null when closable_id is missing', function (): void {
    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
    ]);

    expect($request->closable())->toBeNull();
});

test('closable returns resource model for resource type', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'resource',
        'closable_id' => $resource->id,
    ]);

    $closable = $request->closable();

    expect($closable)->toBeInstanceOf(Resource::class)
        ->and($closable?->id)->toBe($resource->id);
});

test('closableType and closableId accessors return validated values', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(StoreClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closableType())->toBe(Institution::class)
        ->and($request->closableId())->toBe($institution->id);
});

test('authorize returns true when user can create closing for a resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_closings');

    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'resource',
        'closable_id' => $resource->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('closable_id must be a uuid', function (): void {
    $rules = buildFormRequest(StoreClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'closable_id' => 'not-a-uuid',
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('closable_id'))->toBeTrue();
});

test('description field has no required rule so it is optional', function (): void {
    // EmptyStringToNotEmpty would change '' to a non-empty rule string like 'required'
    // A request without description should still pass validation
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(StoreClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
        // No description key
    ], $rules);

    expect($validator->passes())->toBeTrue();
});

test('description key exists in rules', function (): void {
    // RemoveArrayItem would remove the 'description' entry entirely
    $rules = buildFormRequest(StoreClosingRequest::class, [])->rules();

    expect($rules)->toHaveKey('description');
});

test('description rule keeps the exact empty-string placeholder', function (): void {
    expect(buildFormRequest(StoreClosingRequest::class, [])->rules()['description'])->toBe(['']);
});

test('rules returns all required field validation rules', function (): void {
    $request = new StoreClosingRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('closable_id')
        ->and($rules['closable_id'])->toContain('required')
        ->and($rules['closable_id'])->toContain('uuid')
        ->and($rules)->toHaveKey('closable_type')
        ->and($rules['closable_type'])->toContain('required')
        ->and($rules)->toHaveKey('start_date')
        ->and($rules['start_date'])->toContain('required')
        ->and($rules['start_date'])->toContain('date_format:d.m.Y')
        ->and($rules)->toHaveKey('start_time')
        ->and($rules['start_time'])->toContain('required')
        ->and($rules['start_time'])->toContain('date_format:H:i')
        ->and($rules)->toHaveKey('end_date')
        ->and($rules['end_date'])->toContain('required')
        ->and($rules['end_date'])->toContain('date_format:d.m.Y')
        ->and($rules)->toHaveKey('end_time')
        ->and($rules['end_time'])->toContain('required')
        ->and($rules['end_time'])->toContain('date_format:H:i')
        ->and($rules)->toHaveKey('description');
});
