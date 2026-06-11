<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ResourceRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ResourceRequest::class);

uses(RefreshDatabase::class);

test('ResourceRequest is abstract class with rules method', function (): void {
    $reflection = new ReflectionClass(ResourceRequest::class);

    expect($reflection->isAbstract())->toBeTrue()
        ->and($reflection->hasMethod('rules'))->toBeTrue();
});

test('rules contains all expected keys', function (): void {
    $request = new StoreResourceRequest;
    $rules = $request->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('resource_group_id')
        ->toHaveKey('title')
        ->toHaveKey('location')
        ->toHaveKey('location_uri')
        ->toHaveKey('description')
        ->toHaveKey('capacity')
        ->toHaveKey('is_active')
        ->toHaveKey('is_verification_required')
        ->toHaveKey('business_hours')
        ->toHaveKey('business_hours.*.id')
        ->toHaveKey('business_hours.*.start')
        ->toHaveKey('business_hours.*.end')
        ->toHaveKey('business_hours.*.week_days')
        ->toHaveKey('business_hours.*.start_date')
        ->toHaveKey('business_hours.*.end_date');
});

test('id field rules contain sometimes nullable uuid exists resources', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['id'])
        ->toContain('sometimes')
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:resources,id');
});

test('resource_group_id field rules contain required uuid exists resource_groups', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['resource_group_id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resource_groups,id');
});

test('location_uri field rules contain url and nullable', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['location_uri'])
        ->toContain('url')
        ->toContain('nullable');
});

test('capacity field rules contain numeric and gt:0', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['capacity'])
        ->toContain('numeric')
        ->toContain('gt:0');
});

test('is_active field rules contain required and boolean', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['is_active'])
        ->toContain('required')
        ->toContain('boolean');
});

test('is_verification_required field rules contain required and boolean', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['is_verification_required'])
        ->toContain('required')
        ->toContain('boolean');
});

test('business_hours field rules contain array and required_if:is_active,true', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours'])
        ->toContain('array')
        ->toContain('required_if:is_active,true');
});

test('business_hours star id field rules contain required_with:business_hours', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.id'])->toContain('required_with:business_hours');
});

test('business_hours star start field rules contain required_with:business_hours', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.start'])->toContain('required_with:business_hours');
});

test('business_hours star end field rules contain required_with:business_hours', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.end'])->toContain('required_with:business_hours');
});

test('business_hours star week_days field rules contain required_with:business_hours', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.week_days'])->toContain('required_with:business_hours');
});

test('business_hours star start_date field rules contain nullable and date', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.start_date'])
        ->toContain('nullable')
        ->toContain('date');
});

test('business_hours star end_date field rules contain nullable and date', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules['business_hours.*.end_date'])
        ->toContain('nullable')
        ->toContain('date');
});

test('location_uri rejects non-url values', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id])->rules();

    $v = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'capacity' => 1,
        'is_active' => false,
        'is_verification_required' => false,
        'location_uri' => 'not-a-url',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('location_uri'))->toBeTrue();
});

test('capacity rejects zero', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id])->rules();

    $v = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'capacity' => 0,
        'is_active' => false,
        'is_verification_required' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('capacity'))->toBeTrue();
});

test('business_hours required when is_active is true', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = buildFormRequest(StoreResourceRequest::class, [
        'resource_group_id' => $resourceGroup->id,
        'business_hours' => [],
    ])->rules();

    $v = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'capacity' => 1,
        'is_active' => true,
        'is_verification_required' => false,
        'business_hours' => [],
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('business_hours'))->toBeTrue();
});

test('prepareForValidation sets business_hours from input', function (): void {
    $request = buildFormRequest(StoreResourceRequest::class, []);
    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->all())->toHaveKey('business_hours');
});

test('rules contains resource_group_id key', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules)->toHaveKey('resource_group_id');
    expect($rules['resource_group_id'])->toContain('required');
});

test('rules contains title key with RequiredWithTranslationRule', function (): void {
    $rules = (new StoreResourceRequest)->rules();

    expect($rules)->toHaveKey('title');
    expect($rules['title'])->not->toBeEmpty();
});

test('location is required as translated input', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id])->rules();

    $validator = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Resource'),
        'location' => [],
        'description' => Utility::getTranslatable('Description'),
        'capacity' => 1,
        'is_active' => false,
        'is_verification_required' => false,
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('location'))->toBeTrue();
});

test('description is required as translated input', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id])->rules();

    $validator = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Resource'),
        'location' => Utility::getTranslatable('Location'),
        'description' => [],
        'capacity' => 1,
        'is_active' => false,
        'is_verification_required' => false,
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('description'))->toBeTrue();
});

test('businessHours returns non-empty array after validation with business hours data', function (): void {
    // AlwaysReturnEmptyArray mutation on businessHours() would always return [].
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $request = buildFormRequest(StoreResourceRequest::class, [
        'resource_group_id' => $resourceGroup->id,
        'business_hours' => [
            ['id' => 'bh-1', 'start' => '08:00', 'end' => '17:00', 'week_days' => [1, 2, 3]],
        ],
    ]);

    $validator = Validator::make($request->all(), [
        'business_hours' => ['array'],
        'business_hours.*.id' => ['required'],
        'business_hours.*.start' => ['required'],
        'business_hours.*.end' => ['required'],
        'business_hours.*.week_days' => ['required'],
    ]);
    $validator->passes();
    $request->setValidator($validator);

    $businessHours = $request->businessHours();

    expect($businessHours)->not->toBeEmpty()
        ->and($businessHours[0]['id'])->toBe('bh-1');
});
