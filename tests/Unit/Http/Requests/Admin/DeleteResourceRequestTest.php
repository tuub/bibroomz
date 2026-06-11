<?php

use App\Http\Requests\Admin\DeleteResourceRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteResourceRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $request = buildFormRequest(DeleteResourceRequest::class, ['id' => $resource->id]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(DeleteResourceRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target resource', function (): void {
    $request = buildAdminFormRequest(DeleteResourceRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when permissioned user has no target resource', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resources');

    $request = buildAdminFormRequest(DeleteResourceRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks delete_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(DeleteResourceRequest::class, ['id' => $resource->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has delete_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resources');

    $request = buildAdminFormRequest(DeleteResourceRequest::class, ['id' => $resource->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('resource accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resources');

    $request = buildAdminFormRequest(DeleteResourceRequest::class, ['id' => $resource->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resource()->id)->toBe($resource->id);
});

test('resource accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resources');

    $request = buildAdminFormRequest(DeleteResourceRequest::class, ['id' => $resource->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $resource->delete();

    expect(fn () => $request->resource())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteResourceRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:resources,id');
});
