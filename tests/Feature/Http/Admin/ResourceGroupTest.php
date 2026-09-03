<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ResourceGroupController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

covers(ResourceGroupController::class);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(PermissionSeeder::class));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForResourceGroups(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

test('getResourceGroups returns 403 for user without view_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource_group.index', ['institution_id' => $institution->id]))
        ->assertForbidden();
});

test('createResourceGroup returns 403 for user without create_resource_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource_group.create', ['institution_id' => $institution->id]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// From AdminAuthorizationTest — resource group authorization tests
// ---------------------------------------------------------------------------

test('user with unrelated admin access cannot create resource groups in other institutions', function (): void {
    $actorInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $actor = User::factory()->create();

    grantAdminPermission($actor, $actorInstitution, 'view_users');

    $this->actingAs($actor)
        ->post(route('admin.resource_group.store'), [
            'institution_id' => $targetInstitution->id,
            'title' => ['en' => 'Hidden Rooms'],
            'slug' => 'hidden-rooms',
            'term_singular' => ['en' => 'Room'],
            'term_plural' => ['en' => 'Rooms'],
            'description' => ['en' => 'Restricted'],
            'help_uri' => 'https://example.org/help',
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('resource_groups', ['slug' => 'hidden-rooms']);
});

test('user with resource group permission can create resource group in allowed institution', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();

    grantAdminPermission($actor, $institution, 'create_resource_groups');

    $this->actingAs($actor)
        ->post(route('admin.resource_group.store'), [
            'institution_id' => $institution->id,
            'title' => ['en' => 'Allowed Rooms'],
            'slug' => 'allowed-rooms',
            'term_singular' => ['en' => 'Room'],
            'term_plural' => ['en' => 'Rooms'],
            'description' => ['en' => 'Allowed'],
            'help_uri' => 'https://example.org/help',
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    $this->assertDatabaseHas('resource_groups', ['slug' => 'allowed-rooms']);
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — resource group tests
// ---------------------------------------------------------------------------

test('scoped admin without edit_resource_groups cannot update resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource_group.update'), [
            'id' => $resourceGroup->id,
            'institution_id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized'),
            'slug' => 'unauthorized-slug',
            'term_singular' => Utility::getTranslatable('Room'),
            'term_plural' => Utility::getTranslatable('Rooms'),
            'description' => Utility::getTranslatable('Unauthorized update'),
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('resource_groups', ['slug' => 'unauthorized-slug']);
});

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('updateResourceGroup returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'original-rg']);
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resource_groups');
    $this->actingAs($actor);

    $this->post(route('admin.resource_group.update'), [
        'id' => $resourceGroup->id,
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Updated Rooms'),
        'slug' => 'updated-rg',
        'term_singular' => Utility::getTranslatable('Room'),
        'term_plural' => Utility::getTranslatable('Rooms'),
        'description' => Utility::getTranslatable('Updated'),
        'is_active' => true,
        'user_groups' => [],
    ])->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    expect($resourceGroup->fresh()?->slug)->toBe('updated-rg');
});

test('deleteResourceGroup returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'delete_resource_groups');
    $this->actingAs($actor);

    $this->post(route('admin.resource_group.delete'), ['id' => $resourceGroup->id])
        ->assertRedirect(route('admin.resource_group.index', ['institution_id' => $institution->id]));

    $this->assertDatabaseMissing('resource_groups', ['id' => $resourceGroup->id]);
});

test('orderResourceGroups returns ok on success', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resource_groups');
    $this->actingAs($actor);

    $this->post(route('admin.resource_group.order'), [
        'rows' => [['id' => $resourceGroup->id, 'order' => 5]],
    ])->assertOk();

    expect($resourceGroup->fresh()?->order)->toBe(5);
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editResourceGroup returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource_group.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('resourceGroupIndex returns redirect for non-existent institution id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource_group.index', ['institution_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

test('resourceGroupCreate returns redirect for non-existent institution id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->get(route('admin.resource_group.create', ['institution_id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeResourceGroup returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_resource_groups');

    // Provide institution_id to pass authorize(), but omit required title/slug/etc.
    $this->actingAs($actor)
        ->post(route('admin.resource_group.store'), [
            'institution_id' => $institution->id,
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertRedirect();
});

test('updateResourceGroup returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_resource_groups');

    // Provide id and institution_id to pass authorize(), but omit required title/slug/etc.
    $this->actingAs($actor)
        ->post(route('admin.resource_group.update'), [
            'id' => $resourceGroup->id,
            'institution_id' => $institution->id,
            'is_active' => true,
            'user_groups' => [],
        ])
        ->assertRedirect();
});

test('scoped admin without delete_resource_groups cannot delete resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $actor = buildScopedActorForResourceGroups($institution);

    $this->actingAs($actor)
        ->post(route('admin.resource_group.delete'), ['id' => $resourceGroup->id])
        ->assertForbidden();

    $this->assertDatabaseHas('resource_groups', ['id' => $resourceGroup->id]);
});
