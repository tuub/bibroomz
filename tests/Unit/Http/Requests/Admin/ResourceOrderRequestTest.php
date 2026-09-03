<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ResourceOrderRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(ResourceOrderRequest::class);

uses(RefreshDatabase::class);

test('ResourceOrderRequest defines validation rules', function (): void {
    $request = new ResourceOrderRequest;

    expect($request->rules())->toBeArray();
});

test('ResourceOrderRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new ResourceOrderRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $rules = (new ResourceOrderRequest)->rules();

    expect($rules)
        ->toHaveKey('rows.*.id')
        ->toHaveKey('rows.*.order');
});

test('star id field rules contain required uuid exists resources', function (): void {
    $rules = (new ResourceOrderRequest)->rules();

    expect($rules['rows.*.id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resources,id');
});

test('star order field rules contain required and integer', function (): void {
    $rules = (new ResourceOrderRequest)->rules();

    expect($rules['rows.*.order'])
        ->toContain('required')
        ->toContain('integer');
});

test('star id rejects non-uuid', function (): void {
    $rules = (new ResourceOrderRequest)->rules();

    $v = Validator::make(['rows' => [['id' => 'not-a-uuid', 'order' => 1]]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('rows.0.id'))->toBeTrue();
});

test('star order rejects non-integer', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $rules = (new ResourceOrderRequest)->rules();

    $v = Validator::make(['rows' => [['id' => $resource->id, 'order' => 'bad']]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('rows.0.order'))->toBeTrue();
});

test('star id rejects non-existent resource uuid', function (): void {
    $rules = (new ResourceOrderRequest)->rules();

    $v = Validator::make(['rows' => [['id' => (string) Str::uuid(), 'order' => 1]]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('rows.0.id'))->toBeTrue();
});

test('rows returns parsed id-order pairs', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => $resource->id, 'order' => 1]],
    ]);

    $rows = $request->rows();

    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe($resource->id);
});

test('rows handles numeric string order', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => $resource->id, 'order' => '3']],
    ]);

    expect($request->rows()->first()['order'])->toBe(3);
});

test('rows filters out non-array entries', function (): void {
    $request = buildFormRequest(ResourceOrderRequest::class, ['rows' => ['not-an-array']]);

    expect($request->rows()->count())->toBe(0);
});

test('authorize returns false when no user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => $resource->id, 'order' => 1]],
    ]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can update all resources', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => $resource->id, 'order' => 1]],
    ], $admin);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns false when resource not found', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => (string) Str::uuid(), 'order' => 1]],
    ], $admin);

    expect($request->authorize())->toBeFalse();
});

// Mutation-killing tests for rows() method

test('rows filters out array entries without id key', function (): void {
    // BooleanAndToBooleanOr would change "is_array && isset" to "is_array || isset"
    // An array missing 'id' should be filtered out
    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['order' => 1]],
    ]);

    expect($request->rows()->count())->toBe(0);
});

test('rows filters out array entries without order key', function (): void {
    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => (string) Str::uuid()]],
    ]);

    expect($request->rows()->count())->toBe(0);
});

test('rows maps non-string id to empty string', function (): void {
    // EmptyStringToNotEmpty would change '' to something non-empty like 'NOT_EMPTY'
    // When id is not a string (e.g. integer), it must map to ''
    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => 123, 'order' => 1]],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe('');
});

test('rows maps non-numeric string order to 0', function (): void {
    // BooleanAndToBooleanOr would make "is_string && is_numeric" into "is_string || is_numeric"
    // A non-numeric string should produce 0, not (int)'not-a-number'
    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => 'some-id', 'order' => 'not-a-number']],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['order'])->toBe(0);
});

test('rows maps integer order directly without casting (integer order preserved)', function (): void {
    $request = buildFormRequest(ResourceOrderRequest::class, [
        'rows' => [['id' => 'some-id', 'order' => 42]],
    ]);

    expect($request->rows()->first()['order'])->toBe(42);
});
