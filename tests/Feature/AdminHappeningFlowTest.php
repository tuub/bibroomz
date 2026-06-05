<?php

covers(
    App\Http\Controllers\Admin\HappeningController::class,
    App\Services\Admin\HappeningAdminService::class,
    App\Http\Requests\Admin\StoreHappeningRequest::class,
    App\Http\Requests\Admin\UpdateHappeningRequest::class,
    App\Http\Requests\Admin\DeleteHappeningRequest::class,
    App\Http\Requests\Admin\HappeningRequest::class
);

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    config()->set('broadcasting.default', 'log');
});

function adminHappeningFeatureTranslatable(string $value): array
{
    return Utility::getTranslatable($value);
}

/**
 * @param list<string> $permissions
 */
function actingHappeningFeatureAdmin(Institution $institution, array $permissions): User
{
    $user = User::factory()->create([
        'is_system_user' => true,
        'is_admin' => false,
    ]);

    foreach ($permissions as $permission) {
        grantAdminPermission($user, $institution, $permission);
    }

    test()->actingAs($user);

    return $user;
}

test('scoped admins without happening create permission cannot store admin happenings', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create(['is_system_user' => true]);
    $verifier = User::factory()->create(['is_system_user' => true]);

    actingHappeningFeatureAdmin($institution, ['view_happenings']);

    $this->post(route('admin.happening.store'), [
        'start_date' => '04.06.2026',
        'start_time' => '10:00',
        'end_date' => '04.06.2026',
        'end_time' => '12:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Blocked session'),
    ])->assertForbidden();

    $this->assertDatabaseMissing('happenings', ['resource_id' => $resource->id]);
});

test('admin happening routes render and mutate happenings', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $bookedUser = User::factory()->create(['is_system_user' => true]);
    $verifier = User::factory()->create(['is_system_user' => true]);

    actingHappeningFeatureAdmin($institution, [
        'view_happenings',
        'create_happenings',
        'edit_happenings',
        'delete_happenings',
    ]);

    $this->get(route('admin.happening.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Index')
            ->has('happenings'));

    $this->get(route('admin.happening.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Form')
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.store'), [
        'start_date' => '04.06.2026',
        'start_time' => '10:00',
        'end_date' => '04.06.2026',
        'end_time' => '12:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    $happening = Happening::query()->where('resource_id', $resource->id)->firstOrFail();

    $this->get(route('admin.happening.edit', ['id' => $happening->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Form')
            ->where('happening.id', $happening->id)
            ->has('resources')
            ->has('users'));

    $this->post(route('admin.happening.update'), [
        'id' => $happening->id,
        'start_date' => '04.06.2026',
        'start_time' => '11:00',
        'end_date' => '04.06.2026',
        'end_time' => '13:00',
        'resource_id' => $resource->id,
        'user_id_01' => $bookedUser->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'label' => adminHappeningFeatureTranslatable('Updated focus session'),
    ])->assertRedirect(route('admin.happening.index'));

    expect($happening->fresh()->getTranslation('label', 'en'))->toBe('Updated focus session');

    $this->post(route('admin.happening.delete'), ['id' => $happening->id])
        ->assertRedirect(route('admin.happening.index'));

    $this->assertSoftDeleted('happenings', ['id' => $happening->id]);
});
