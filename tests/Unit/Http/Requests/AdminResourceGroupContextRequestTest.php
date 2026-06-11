<?php

use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceGroupContextRequest::class);

uses(RefreshDatabase::class);

test('resource group context request defines validation rules', function (): void {
    $request = new ResourceGroupContextRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('resource_group_id')
        ->and($rules['resource_group_id'])->toBeArray()
        ->and($rules['resource_group_id'])->toContain('required')
        ->and($rules['resource_group_id'])->toContain('uuid')
        ->and($rules['resource_group_id'])->toContain('exists:resource_groups,id');
});

test('resource group context request requires resource group id', function (): void {
    $request = new ResourceGroupContextRequest([]);
    $rules = $request->rules();

    $validator = validator([], $rules);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resource_group_id'))->toBeTrue();
});

test('resource group context request validates uuid format', function (): void {
    $request = new ResourceGroupContextRequest(['resource_group_id' => 'invalid']);
    $rules = $request->rules();

    $validator = validator(['resource_group_id' => 'invalid'], $rules);
    expect($validator->fails())->toBeTrue();
});

test('resource group context request validates resource group exists', function (): void {
    $fakeId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
    $request = new ResourceGroupContextRequest(['resource_group_id' => $fakeId]);
    $rules = $request->rules();

    $validator = validator(['resource_group_id' => $fakeId], $rules);
    expect($validator->fails())->toBeTrue();
});

test('resource group context request passes with valid resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = new ResourceGroupContextRequest(['resource_group_id' => $resourceGroup->id]);
    $rules = $request->rules();

    $validator = validator(['resource_group_id' => $resourceGroup->id], $rules);
    expect($validator->passes())->toBeTrue();
});

test('resource group context request validation includes all required rules', function (): void {
    $request = new ResourceGroupContextRequest;
    $rules = $request->rules();

    $rulesArray = (array) $rules['resource_group_id'];
    expect($rulesArray)->toHaveCount(3)
        ->and($rulesArray[0])->toBe('required')
        ->and($rulesArray[1])->toBe('uuid')
        ->and($rulesArray[2])->toBe('exists:resource_groups,id');
});
