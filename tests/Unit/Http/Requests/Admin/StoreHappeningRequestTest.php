<?php

declare(strict_types=1);

use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;

covers(StoreHappeningRequest::class);

uses(RefreshDatabase::class);

test('store happening request denies non-admin user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user);
    $request = new StoreHappeningRequest;
    $request->setRouteResolver(fn () => tap(new Route('POST', '/', []), fn ($r) => $r->bind(request())));

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user is null', function (): void {
    // InstanceOfToTrue on $user instanceof User would make it always true.
    // Test with no user: must return false.
    $request = new StoreHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource is null', function (): void {
    // InstanceOfToTrue on $resource instanceof Resource would make it always true.
    // With user but no resource_id: resource() returns null → authorize must return false.
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildFormRequest(StoreHappeningRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when resource exists but user cannot create', function (): void {
    // BooleanAndToBooleanOr: $user instanceof User && $resource instanceof Resource && ... becomes ||
    // Kill it by having user + resource valid but can('adminCreate') = false (non-admin user without permission)
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $request = buildFormRequest(StoreHappeningRequest::class, ['resource_id' => $resource->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can create happening for resource (full path)', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(StoreHappeningRequest::class, ['resource_id' => $resource->id], $user);

    expect($request->authorize())->toBeTrue();
});
