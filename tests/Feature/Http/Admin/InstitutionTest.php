<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\InstitutionController;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\WeekDay;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(InstitutionController::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Create an actor who can access the admin panel but has no permissions in the target institution. */
function buildScopedActorForInstitutions(Institution $institution): User
{
    $actor = User::factory()->create();
    grantAdminPermission($actor, $institution, 'view_users');

    return $actor;
}

// ---------------------------------------------------------------------------
// From Http/Controllers/Admin/InstitutionControllerTest
// ---------------------------------------------------------------------------

test('InstitutionController can be resolved from container', function (): void {
    $controller = app(InstitutionController::class);

    expect($controller)->toBeInstanceOf(InstitutionController::class);
});

test('createInstitution returns 403 for user without create permission', function (): void {
    // RemoveMethodCall would remove $this->authorize('create', Institution::class)
    // This tests that the authorize() call is actually enforced.
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.institution.create'))
        ->assertForbidden();
});

test('storeInstitution returns 403 for user without create permission', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->post(route('admin.institution.store'), [
        'title' => ['en' => 'Test'],
        'slug' => 'test-inst',
    ])->assertForbidden();
});

test('editInstitution returns 403 for user without edit permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $this->get(route('admin.institution.edit', ['id' => $institution->id]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// From AdminPermissionMatrixTest — institution tests
// ---------------------------------------------------------------------------

test('scoped admin without edit_institutions cannot update institution', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForInstitutions($institution);

    $this->actingAs($actor)
        ->post(route('admin.institution.update'), [
            'id' => $institution->id,
            'title' => Utility::getTranslatable('Unauthorized Update'),
            'short_title' => 'UU',
            'slug' => 'unauthorized-update',
            'location' => 'Berlin',
            'week_days' => [],
            'home_uri' => 'https://example.org',
            'logo_uri' => 'https://example.org/logo.png',
            'teaser_uri' => 'https://example.org/teaser.png',
            'email' => 'info@example.org',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('institutions', ['slug' => 'unauthorized-update']);
});

test('scoped admin without delete_institutions cannot delete institution', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForInstitutions($institution);

    $this->actingAs($actor)
        ->post(route('admin.institution.delete'), ['id' => $institution->id])
        ->assertForbidden();

    $this->assertDatabaseHas('institutions', ['id' => $institution->id]);
});

// ---------------------------------------------------------------------------
// Success paths
// ---------------------------------------------------------------------------

test('storeInstitution returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $weekDayIds = WeekDay::query()->pluck('id')->all();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_institutions');
    $this->actingAs($actor);

    $this->post(route('admin.institution.store'), [
        'title' => Utility::getTranslatable('New Institution'),
        'short_title' => 'NI',
        'slug' => 'new-institution',
        'location' => 'Berlin',
        'week_days' => $weekDayIds,
        'home_uri' => 'https://example.org',
        'logo_uri' => 'https://example.org/logo.png',
        'teaser_uri' => 'https://example.org/teaser.png',
        'email' => 'info@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    $this->assertDatabaseHas('institutions', ['slug' => 'new-institution']);
});

test('updateInstitution returns redirect on success', function (): void {
    $institution = Institution::factory()->create(['slug' => 'original-inst']);
    $weekDayIds = WeekDay::query()->pluck('id')->all();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_institutions');
    $this->actingAs($actor);

    $this->post(route('admin.institution.update'), [
        'id' => $institution->id,
        'title' => Utility::getTranslatable('Updated Institution'),
        'short_title' => 'UI',
        'slug' => 'updated-institution',
        'location' => 'Berlin',
        'week_days' => $weekDayIds,
        'home_uri' => 'https://example.org',
        'logo_uri' => 'https://example.org/logo.png',
        'teaser_uri' => 'https://example.org/teaser.png',
        'email' => 'updated@example.org',
        'is_active' => true,
    ])->assertRedirect(route('admin.institution.index'));

    expect($institution->fresh()?->slug)->toBe('updated-institution');
});

test('deleteInstitution returns redirect on success', function (): void {
    $institution = Institution::factory()->create();
    $deleteTarget = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'delete_institutions');
    $this->actingAs($actor);

    $this->post(route('admin.institution.delete'), ['id' => $deleteTarget->id])
        ->assertRedirect(route('admin.institution.index'));

    $this->assertDatabaseMissing('institutions', ['id' => $deleteTarget->id]);
});

test('orderInstitutions returns ok on success', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_institutions');
    $this->actingAs($actor);

    $this->post(route('admin.institution.order'), [
        'rows' => [['id' => $institution->id, 'order' => 3]],
    ])->assertOk();

    expect($institution->fresh()?->order)->toBe(3);
});

// ---------------------------------------------------------------------------
// Redirect for non-existent ID
// ---------------------------------------------------------------------------

test('editInstitution returns redirect for non-existent id', function (): void {
    $institution = Institution::factory()->create();
    $actor = buildScopedActorForInstitutions($institution);

    $this->actingAs($actor)
        ->get(route('admin.institution.edit', ['id' => (string) Str::uuid()]))
        ->assertRedirect();
});

// ---------------------------------------------------------------------------
// Redirect on validation failure (form POST)
// ---------------------------------------------------------------------------

test('storeInstitution returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'create_institutions');

    $this->actingAs($actor)
        ->post(route('admin.institution.store'), [])
        ->assertRedirect();
});

test('updateInstitution returns redirect when required fields are missing', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create(['is_admin' => false]);
    grantAdminPermission($actor, $institution, 'edit_institutions');

    $this->actingAs($actor)
        ->post(route('admin.institution.update'), ['id' => $institution->id])
        ->assertRedirect();
});
