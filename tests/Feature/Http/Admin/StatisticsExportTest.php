<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\StatisticsController;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(StatisticsController::class);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(PermissionSeeder::class));

test('admin statistics export returns a CSV for each export type for a user permitted to view the statistics page', function (string $type, string $expectedHeader): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    Happening::factory()->count(2)->for($resource, 'resource')->create();

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $response = $this->actingAs($admin)->get(route('admin.statistics.export', ['type' => $type]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=utf-8');

    expect($response->streamedContent())->toContain($expectedHeader);
})->with([
    ['time_series', 'Label,Count'],
    ['institutions', 'Title,Active,Cancelled,"Cancellation Rate"'],
    ['resource_groups', 'Title,Institution,Active,Cancelled,"Cancellation Rate"'],
    ['resources', 'Title,"Resource Group",Active,Cancelled,"Cancellation Rate"'],
    ['heatmap', '"Day of Week",Hour,Count,Percentage'],
]);

test('admin statistics export resolves parent institution and resource group names instead of raw IDs', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $admin = User::factory()->create();
    grantAdminPermission($admin, $institution, 'view_happenings');

    $resourceGroupsCsv = $this->actingAs($admin)
        ->get(route('admin.statistics.export', ['type' => 'resource_groups']))
        ->streamedContent();

    expect($resourceGroupsCsv)->toContain($institution->getTranslation('title', 'en'))
        ->not->toContain($institution->id);

    $resourcesCsv = $this->actingAs($admin)
        ->get(route('admin.statistics.export', ['type' => 'resources']))
        ->streamedContent();

    expect($resourcesCsv)->toContain($resourceGroup->getTranslation('title', 'en'))
        ->not->toContain($resourceGroup->id);
});

test('admin statistics export rejects an invalid type', function (): void {
    $admin = User::factory()->create();
    grantAdminPermission($admin, Institution::factory()->create(), 'view_happenings');

    $this->actingAs($admin)
        ->get(route('admin.statistics.export', ['type' => 'not_a_real_type']))
        ->assertInvalid('type');
});

test('admin statistics export returns 403 for user without admin permission', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.statistics.export', ['type' => 'institutions']))
        ->assertForbidden();
});

test('admin statistics export redirects unauthenticated guest', function (): void {
    $this->get(route('admin.statistics.export', ['type' => 'institutions']))
        ->assertRedirect();
});
