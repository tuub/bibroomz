<?php

covers(
    App\Services\Happenings\AdminHappeningPresenter::class,
    App\Services\Happenings\ListAdminHappeningsAction::class,
    App\Services\Resources\ResourceVisibilityService::class
);

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00'));
    config()->set('broadcasting.default', 'log');
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function buildHappeningIndexFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => false,
    ]);
    $owner = User::factory()->create(['name' => 'happening.index.owner', 'is_system_user' => true]);
    $verifier = User::factory()->create(['name' => 'happening.index.verifier', 'is_system_user' => true]);

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $verifier->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => '2026-06-10 08:00:00',
        'verified_at' => '2026-06-10 08:05:00',
        'label' => Utility::getTranslatable('Index Test Booking'),
    ]);

    $admin = User::factory()->create(['is_system_user' => true, 'is_admin' => false]);
    foreach (['view_happenings', 'create_happenings', 'edit_happenings', 'delete_happenings'] as $perm) {
        grantAdminPermission($admin, $institution, $perm);
    }
    test()->actingAs($admin);

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier', 'happening', 'admin');
}

test('admin happenings index presents happening data including institution and resource group', function () {
    $fixture = buildHappeningIndexFixture();

    $this->get(route('admin.happening.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Happenings/Index')
            ->has('happenings', 1, fn (Assert $h) => $h
                ->where('id', $fixture['happening']->id)
                ->where('is_verified', true)
                ->where('user1', $fixture['owner']->name)
                ->where('user2', $fixture['verifier']->name)
                ->has('institution')
                ->has('resource_group')
                ->has('resource')
                ->etc()));
});

test('admin resource index filters resources through visibility service for authenticated admin', function () {
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
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Resources/Index')
            ->has('resources', 1));
});
