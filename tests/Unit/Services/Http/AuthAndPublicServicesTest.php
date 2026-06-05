<?php

covers(
    App\Services\Http\LoginAction::class,
    App\Services\Http\LogoutAction::class,
    App\Services\Http\HomePageDataBuilder::class,
    App\Services\Http\PublicResourcePresenter::class,
    App\Services\Http\CurrentUserStatusBuilder::class,
    App\Services\Http\InertiaSharedDataBuilder::class,
    App\Services\Http\LocalePreferenceManager::class,
    App\Services\Http\UserActivityRecorder::class
);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\LoginAction;
use App\Services\Http\PublicResourcePresenter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('login action returns null when authentication fails', function () {
    Auth::shouldReceive('attempt')
        ->once()
        ->with(['username' => 'missing.user', 'password' => 'wrong-secret'])
        ->andReturnFalse();

    $request = request()->create('/login', 'POST');

    expect((new LoginAction())->execute($request, [
        'username' => 'missing.user',
        'password' => 'wrong-secret',
    ]))->toBeNull();
});

test('public resource presenter adds a fallback business hour entry when none are available', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $resource->business_hours()->delete();
    $resource->load('business_hours.week_days', 'resource_group');

    $payload = app(PublicResourcePresenter::class)->present(
        $resource,
        $resourceGroup,
        CarbonImmutable::parse('2026-06-03 00:00:00'),
    );

    $fallback = $payload['businessHours']->first();

    expect($payload['businessHours'])->toHaveCount(1)
        ->and($fallback['startTime'])->toBe('')
        ->and($fallback['endTime'])->toBe('')
        ->and($fallback['daysOfWeek'])->toBeEmpty();
});

// Regression: HomePageDataBuilder::buildStartPageData used ->with(['resource_groups']) without an
// is_active constraint, so inactive resource groups were eager-loaded and passed to the frontend
// inside the serialised institutions prop.
test('start page data excludes inactive resource groups from the institutions payload', function () {
    $institution = Institution::factory()->create(['is_active' => true]);

    $active = ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);
    ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => true]);
    $inactive = ResourceGroup::factory()->for($institution, 'institution')->create(['is_active' => false]);

    $data = app(HomePageDataBuilder::class)->buildStartPageData();
    $institutionsInPayload = collect($data['props']['institutions'] ?? []);
    $matchingInstitution = $institutionsInPayload->firstWhere('id', $institution->id);

    expect($data)->toHaveKey('props')
        ->and($matchingInstitution)->not->toBeNull();

    $resourceGroupIds = collect($matchingInstitution->resource_groups)->pluck('id');

    expect($resourceGroupIds)->not->toContain($inactive->id)
        ->and($resourceGroupIds)->toContain($active->id);
});
