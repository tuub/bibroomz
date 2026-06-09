<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\AdminRouteRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(
    AdminController::class,
    AdminRouteRequest::class
);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(PermissionSeeder::class));

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

test('user with unrelated admin access cannot remove members from foreign user groups', function (): void {
    $actorInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();

    grantAdminPermission($actor, $actorInstitution, 'view_users');

    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $targetInstitution->id,
    ]);
    $userGroup->users()->attach($member);

    $this->actingAs($actor)
        ->post(route('admin.user_group.users.remove'), ['id' => $userGroup->id, 'users' => [$member->id]])
        ->assertForbidden();

    $this->assertDatabaseHas('user_group_user', ['user_group_id' => $userGroup->id, 'user_id' => $member->id]);
});
