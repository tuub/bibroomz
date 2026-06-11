<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(UpdateResourceRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include id resource_group_id and resource fields', function (): void {
    $request = buildFormRequest(UpdateResourceRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules)->toHaveKey('resource_group_id')
        ->and($rules)->toHaveKey('title')
        ->and($rules)->toHaveKey('is_active')
        ->and($rules)->toHaveKey('capacity');
});

test('authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(UpdateResourceRequest::class, []);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateResourceRequest::class, ['resource_group_id' => $resourceGroup->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_resources permission in same group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_resources permission in same group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_resources');

    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize uses create permission when moving resource to a different group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup1 = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resourceGroup2 = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup1, 'resource_group')->create();
    $user = User::factory()->create();

    // No permission for cross-group create → false
    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup2->id,
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true for cross-group move when user has create_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup1 = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resourceGroup2 = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup1, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_resources');

    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup2->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

test('resourceOrNull returns null when no id given', function (): void {
    $request = buildFormRequest(UpdateResourceRequest::class, []);

    expect($request->resourceOrNull())->toBeNull();
});

test('resourceOrNull returns the resource model for a valid id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(UpdateResourceRequest::class, ['id' => $resource->id]);

    expect($request->resourceOrNull()?->id)->toBe($resource->id);
});

test('resource accessor returns the model after validation', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildAdminFormRequest(UpdateResourceRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resource()->id)->toBe($resource->id);
});

test('resource accessor throws ModelNotFoundException when model not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildAdminFormRequest(UpdateResourceRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $resource->forceDelete();

    expect(fn () => $request->resource())->toThrow(ModelNotFoundException::class);
});

test('resourceGroup override returns resource group by resource_group_id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildFormRequest(UpdateResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);

    expect($request->resourceGroup()?->id)->toBe($resourceGroup->id);
});
