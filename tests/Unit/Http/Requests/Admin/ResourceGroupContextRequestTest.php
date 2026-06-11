<?php

use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ResourceGroupContextRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(ResourceGroupContextRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('resourceGroup accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ResourceGroupContextRequest::class, ['resource_group_id' => $resourceGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceGroup()->id)->toBe($resourceGroup->id);
});

test('resourceGroup accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ResourceGroupContextRequest::class, ['resource_group_id' => $resourceGroup->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $resourceGroup->delete();

    expect(fn () => $request->resourceGroup())->toThrow(ModelNotFoundException::class);
});
