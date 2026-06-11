<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ResourceController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithPermissions;

covers(ResourceController::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForResources(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/ResourceControllerTest
// ---------------------------------------------------------------------------

test('createResource returns 403 for user without create permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('create', [...]) entirely
    // RemoveArrayItem would remove an item from the authorize() array argument
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.resource.create', ['resource_group_id' => $resourceGroup->id]))
        ->assertForbidden();
});

test('editResource returns 403 for user without edit permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('edit', $resource)
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.resource.edit', ['id' => $resource->id]))
        ->assertForbidden();
});

test('createResource loads institution relationship on resource group', function (): void {
    // RemoveArrayItem on $this->authorize('create', [Resource::class, $resourceGroup->institution])
    // would remove $resourceGroup->institution from the array, making the policy check fail.
    // With the correct array, an admin user should be authorized.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $this->get(route('admin.resource.create', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk();
});

test('editResource loads business_hours relationship', function (): void {
    // RemoveArrayItem on $resource->load(['business_hours', 'business_hours.week_days:id'])
    // With an admin, the edit page should load correctly with business_hours.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($user);

    $this->get(route('admin.resource.edit', ['id' => $resource->id]))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// From AdminHappeningIndexAndVisibilityTest — resource visibility test
// ---------------------------------------------------------------------------

test('admin resource index filters resources through visibility service for authenticated admin', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => false,
    ]);

    $admin = User::factory()->create(['is_system_user' => true, 'is_admin' => false]);
    foreach (['view_resources', 'create_resources', 'edit_resources', 'delete_resources'] as $perm) {
        grantAdminPermission($admin, $institution, $perm);
    }
    $this->actingAs($admin);

    $this->get(route('admin.resource.index', ['resource_group_id' => $resourceGroup->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Admin/Resources/Index')
            ->has('resources', 1));
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — resource tests
// ---------------------------------------------------------------------------

test('scoped admin without create_resources cannot store resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.store'), [
            'resource_group_id' => $resourceGroup->id,
            'title' => Utility::getTranslatable('Desk X'),
            'location' => Utility::getTranslatable('Floor 1'),
            'location_uri' => 'https://example.org/map',
            'description' => Utility::getTranslatable('A desk'),
            'capacity' => 1,
            'is_active' => true,
            'is_verification_required' => false,
            'business_hours' => [[
                'id' => (string) Str::uuid(),
                'start' => '08:00',
                'end' => '18:00',
                'week_days' => [],
                'start_date' => null,
                'end_date' => null,
            ]],
        ])
        ->assertForbidden();
});

test('scoped admin without edit_resources cannot update resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.update'), [
            'id' => $resource->id,
            'resource_group_id' => $resourceGroup->id,
            'title' => Utility::getTranslatable('Unauthorized Update'),
            'location' => Utility::getTranslatable('Floor 1'),
            'location_uri' => 'https://example.org/map',
            'description' => Utility::getTranslatable('A desk'),
            'capacity' => 1,
            'is_active' => true,
            'is_verification_required' => false,
            'business_hours' => [],
        ])
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editResource returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('resourceIndex returns redirect for non-existent resource group id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource.index', ['resource_group_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('resourceCreate returns redirect for non-existent resource group id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource.create', ['resource_group_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeResource returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_resources');

    // Provide resource_group_id to pass authorize(), and empty locale arrays to trigger
    // RequiredWithTranslationRule failure on title/location/description → redirect
    $this->actingAs($actor)
        ->post(route('admin.resource.store'), [
            'resource_group_id' => $resourceGroup->id,
            'title' => ['en' => '', 'de' => ''],
            'location' => ['en' => '', 'de' => ''],
            'description' => ['en' => '', 'de' => ''],
            'is_active' => false,
            'is_verification_required' => false,
            'business_hours' => [],
        ])
        ->assertRedirect();
});

test('updateResource returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resources');

    // Provide id and resource_group_id to pass authorize(), but omit required title/location/etc.
    $this->actingAs($actor)
        ->post(route('admin.resource.update'), [
            'id' => $resource->id,
            'resource_group_id' => $resourceGroup->id,
            'is_active' => false,
            'is_verification_required' => false,
            'business_hours' => [],
        ])
        ->assertRedirect();
});

test('scoped admin without delete_resources cannot delete resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.delete'), ['id' => $resource->id])
        ->assertForbidden();

    $this->assertDatabaseHas('resources', ['id' => $resource->id]);
});

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('storeResource returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_resources');
    $this->actingAs($actor);

    $this->post(route('admin.resource.store'), [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('New Desk'),
        'location' => Utility::getTranslatable('Floor 2'),
        'location_uri' => null,
        'description' => Utility::getTranslatable('A quiet desk'),
        'capacity' => 1,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ])->assertRedirect();

    $this->assertDatabaseHas('resources', ['resource_group_id' => $resourceGroup->id]);
});

test('updateResource returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resources');
    $this->actingAs($actor);

    $this->post(route('admin.resource.update'), [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Updated Desk'),
        'location' => Utility::getTranslatable('Floor 3'),
        'location_uri' => null,
        'description' => Utility::getTranslatable('Updated description'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ])->assertRedirect();
});

test('deleteResource returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'delete_resources');
    $this->actingAs($actor);

    $this->post(route('admin.resource.delete'), ['id' => $resource->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
});

test('cloneResource returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_resources');
    $this->actingAs($actor);

    $this->post(route('admin.resource.clone'), ['id' => $resource->id])
        ->assertRedirect();

    expect(Resource::where('resource_group_id', $resourceGroup->id)->count())->toBe(2);
});

test('orderResources returns ok on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resources');
    $this->actingAs($actor);

    $this->post(route('admin.resource.order'), [
        ['id' => $resource->id, 'order' => 7],
    ])->assertOk();

    expect($resource->fresh()?->order)->toBe(7);
});

// ---------------------------------------------------------------------------
// 403 for clone and order (no existing 403 tests for these routes)
// ---------------------------------------------------------------------------

test('cloneResource returns 403 for user without create_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.clone'), ['id' => $resource->id])
        ->assertForbidden();

    expect(Resource::where('resource_group_id', $resourceGroup->id)->count())->toBe(1);
});

test('orderResources returns 403 for user without edit_resources permission', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $actor = buildScopedActorForResources($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource.order'), [
            ['id' => $resource->id, 'order' => 1],
        ])
        ->assertForbidden();
});
