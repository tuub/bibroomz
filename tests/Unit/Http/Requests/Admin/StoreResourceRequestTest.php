<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreResourceRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(StoreResourceRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include resource group and required fields', function (): void {
    $request = buildFormRequest(StoreResourceRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('resource_group_id')
        ->and($rules)->toHaveKey('title')
        ->and($rules)->toHaveKey('is_active')
        ->and($rules)->toHaveKey('is_verification_required')
        ->and($rules)->toHaveKey('capacity')
        ->and($rules)->toHaveKey('business_hours');
});

test('authorize returns false when no user is authenticated', function (): void {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $request = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource_group not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(StoreResourceRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks create_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has create_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_resources');

    $request = buildAdminFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('resourceGroup accessor returns the resource group model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);

    expect($request->resourceGroup()?->id)->toBe($resourceGroup->id);
});

test('resourceGroup accessor returns null when resource_group_id is absent', function (): void {
    $request = buildFormRequest(StoreResourceRequest::class, []);

    expect($request->resourceGroup())->toBeNull();
});

test('validation passes with full valid resource data', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $data = [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A nice desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildFormRequest(StoreResourceRequest::class, $data);
    $validator = Validator::make($request->validationData(), $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('resourceData excludes business_hours and businessHours returns the array', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildAdminFormRequest(StoreResourceRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceData())->toHaveKey('title')
        ->and($request->resourceData())->not->toHaveKey('business_hours')
        ->and($request->businessHours())->toBe([]);
});

test('location_uri must be a url when present', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $request = buildFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    $rules = $request->rules();

    $validator = Validator::make([
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'capacity' => 1,
        'is_active' => false,
        'is_verification_required' => false,
        'location_uri' => 'not-a-url',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('location_uri'))->toBeTrue();
});
