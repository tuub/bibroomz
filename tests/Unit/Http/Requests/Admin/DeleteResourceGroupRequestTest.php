<?php

use App\Http\Requests\Admin\DeleteResourceGroupRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteResourceGroupRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $request = buildFormRequest(DeleteResourceGroupRequest::class, ['id' => $resourceGroup->id]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource group not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when admin user has no target resource group', function (): void {
    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, [], User::factory()->create(['is_admin' => true]));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when permissioned user has no target resource group', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');

    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks delete_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, ['id' => $resourceGroup->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has delete_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');

    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, ['id' => $resourceGroup->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('resourceGroup accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');

    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, ['id' => $resourceGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceGroup()->id)->toBe($resourceGroup->id);
});

test('resourceGroup accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_resource_groups');

    $request = buildAdminFormRequest(DeleteResourceGroupRequest::class, ['id' => $resourceGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $resourceGroup->delete();

    expect(fn () => $request->resourceGroup())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteResourceGroupRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:resource_groups,id');
});
