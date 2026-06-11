<?php

declare(strict_types=1);

use App\Library\Utility;
use App\Models\Institution;
use App\Models\InstitutionUserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(InstitutionUserRole::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

test('institution user role institution relationship returns BelongsTo', function (): void {
    $pivot = new InstitutionUserRole;

    expect($pivot->institution())->toBeInstanceOf(BelongsTo::class);
});

test('institution user role role relationship returns BelongsTo', function (): void {
    $pivot = new InstitutionUserRole;

    expect($pivot->role())->toBeInstanceOf(BelongsTo::class);
});

test('institution user role user relationship returns BelongsTo', function (): void {
    $pivot = new InstitutionUserRole;

    expect($pivot->user())->toBeInstanceOf(BelongsTo::class);
});

test('institution user role is created via institution users relationship', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $role = Role::create([
        'name' => Utility::getTranslatable('Admin'),
        'description' => Utility::getTranslatable('Administrator role'),
    ]);

    $institution->users()->attach($user->id, ['role_id' => $role->id]);

    expect($institution->users()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('institution user role hasPermission returns true when institution matches and role has permission', function (): void {
    $institution = Institution::factory()->create();

    $role = Role::create([
        'name' => Utility::getTranslatable('Viewer'),
        'description' => Utility::getTranslatable('View role'),
    ]);

    $permission = Permission::firstWhere('key', 'view_mails');
    $role->permissions()->attach($permission);
    $role->load('permissions');

    $pivot = new InstitutionUserRole;
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('role', $role);

    expect($pivot->hasPermission('view_mails', $institution))->toBeTrue();
});

test('institution user role hasPermission returns false when institution does not match', function (): void {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();

    $role = Role::create([
        'name' => Utility::getTranslatable('Editor'),
        'description' => Utility::getTranslatable('Edit role'),
    ]);

    $permission = Permission::firstWhere('key', 'view_mails');
    $role->permissions()->attach($permission);
    $role->load('permissions');

    $pivot = new InstitutionUserRole;
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('role', $role);

    expect($pivot->hasPermission('view_mails', $otherInstitution))->toBeFalse();
});

test('institution user role pivot can be retrieved from the relationship', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $role = Role::create([
        'name' => Utility::getTranslatable('Test role'),
        'description' => Utility::getTranslatable('Desc'),
    ]);

    $institution->users()->attach($user->id, ['role_id' => $role->id]);

    $pivot = InstitutionUserRole::where('user_id', $user->id)
        ->where('institution_id', $institution->id)
        ->firstOrFail();

    expect($pivot)->toBeInstanceOf(InstitutionUserRole::class);
});
