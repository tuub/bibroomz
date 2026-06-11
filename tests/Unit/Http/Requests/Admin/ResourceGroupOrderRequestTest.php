<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ResourceGroupOrderRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(ResourceGroupOrderRequest::class);

uses(RefreshDatabase::class);

test('ResourceGroupOrderRequest defines validation rules', function (): void {
    $request = new ResourceGroupOrderRequest;

    expect($request->rules())->toBeArray();
});

test('ResourceGroupOrderRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new ResourceGroupOrderRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules contains all expected keys', function (): void {
    $rules = (new ResourceGroupOrderRequest)->rules();

    expect($rules)
        ->toHaveKey('*.id')
        ->toHaveKey('*.order');
});

test('star id field rules contain required uuid exists resource_groups', function (): void {
    $rules = (new ResourceGroupOrderRequest)->rules();

    expect($rules['*.id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resource_groups,id');
});

test('star order field rules contain required and integer', function (): void {
    $rules = (new ResourceGroupOrderRequest)->rules();

    expect($rules['*.order'])
        ->toContain('required')
        ->toContain('integer');
});

test('star id rejects non-uuid', function (): void {
    $rules = (new ResourceGroupOrderRequest)->rules();

    $v = Validator::make([['id' => 'not-a-uuid', 'order' => 1]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('0.id'))->toBeTrue();
});

test('star order rejects non-integer', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = (new ResourceGroupOrderRequest)->rules();

    $v = Validator::make([['id' => $resourceGroup->id, 'order' => 'bad']], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('0.order'))->toBeTrue();
});

test('star id rejects non-existent resource group uuid', function (): void {
    $rules = (new ResourceGroupOrderRequest)->rules();

    $v = Validator::make([['id' => (string) Str::uuid(), 'order' => 1]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('0.id'))->toBeTrue();
});

test('rows returns parsed id-order pairs', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => $resourceGroup->id, 'order' => 2],
    ]);

    $rows = $request->rows();

    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe($resourceGroup->id);
});

test('rows handles numeric string order', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => $resourceGroup->id, 'order' => '5'],
    ]);

    expect($request->rows()->first()['order'])->toBe(5);
});

test('rows filters out non-array entries', function (): void {
    $request = buildFormRequest(ResourceGroupOrderRequest::class, ['not-an-array']);

    expect($request->rows()->count())->toBe(0);
});

test('authorize returns false when no user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => $resourceGroup->id, 'order' => 1],
    ]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can update all resource groups', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => $resourceGroup->id, 'order' => 1],
    ], $admin);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns false when resource group not found', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => (string) Str::uuid(), 'order' => 1],
    ], $admin);

    expect($request->authorize())->toBeFalse();
});

test('rows filters out array entries without id key', function (): void {
    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['order' => 1],
    ]);

    expect($request->rows()->count())->toBe(0);
});

test('rows maps non-string id to empty string', function (): void {
    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => 99, 'order' => 1],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe('');
});

test('rows maps non-numeric string order to 0', function (): void {
    $request = buildFormRequest(ResourceGroupOrderRequest::class, [
        ['id' => 'some-id', 'order' => 'nope'],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['order'])->toBe(0);
});
