<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Requests\ResourceGroupRouteRequest;
use App\Http\Requests\SwitchLanguageRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\InstitutionAccessService;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\RouteResourceGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(HomeController::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('HomeController can be resolved from container', function (): void {
    $controller = app(HomeController::class);

    expect($controller)->toBeInstanceOf(HomeController::class);
});

test('getStart renders Start Inertia page when no redirect in data', function (): void {
    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn(['props' => ['appName' => 'Roomz']]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getStart redirects when data contains valid redirect slugs', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn([
            'redirect' => [
                'institution_slug' => $institution->slug,
                'resource_group_slug' => $resourceGroup->slug,
            ],
        ]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toContain($institution->slug);
});

test('getStart renders Start page when only institutionSlug is non-string', function (): void {
    // BooleanOrToBooleanAnd would change || to &&, making the guard only trigger when BOTH are non-string.
    // This test provides null institutionSlug but a string resourceGroupSlug.
    // The OR guard should still render Start even with only one non-string slug.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn([
            'redirect' => [
                'institution_slug' => null,         // non-string
                'resource_group_slug' => $resourceGroup->slug, // string
            ],
        ]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getStart renders Start page when only resourceGroupSlug is non-string', function (): void {
    $institution = Institution::factory()->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn([
            'redirect' => [
                'institution_slug' => $institution->slug, // string
                'resource_group_slug' => null,             // non-string
            ],
        ]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getStart renders Start page when redirect has non-string slugs', function (): void {
    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn(['redirect' => ['institution_slug' => null, 'resource_group_slug' => null]]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getStart uses empty props array when props key is not an array', function (): void {
    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);

    $builder->shouldReceive('buildStartPageData')
        ->once()
        ->andReturn(['props' => 'not-an-array']);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))->getStart();

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getInstitutionalHome renders Home Inertia page when IP is allowed', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);
    $request = Mockery::mock(ResourceGroupRouteRequest::class);

    $request->shouldReceive('institutionSlug')->once()->andReturn($institution->slug);
    $request->shouldReceive('resourceGroupSlug')->once()->andReturn($resourceGroup->slug);
    $request->shouldReceive('ip')->once()->andReturn('127.0.0.1');

    $resolver->shouldReceive('resolve')
        ->once()
        ->with($institution->slug, $resourceGroup->slug, ['institution.settings', 'institution.week_days', 'settings'])
        ->andReturn($resourceGroup);

    $accessService->shouldReceive('isIpAllowed')
        ->once()
        ->andReturn(true);

    $builder->shouldReceive('buildHomePageData')
        ->once()
        ->with($resourceGroup)
        ->andReturn(['resourceGroup' => $resourceGroup]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))
        ->getInstitutionalHome($request);

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('getInstitutionalHome redirects to start when IP is blocked', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);
    $request = Mockery::mock(ResourceGroupRouteRequest::class);

    $request->shouldReceive('institutionSlug')->once()->andReturn($institution->slug);
    $request->shouldReceive('resourceGroupSlug')->once()->andReturn($resourceGroup->slug);
    $request->shouldReceive('ip')->once()->andReturn('10.0.0.99');

    $resolver->shouldReceive('resolve')
        ->once()
        ->andReturn($resourceGroup);

    $accessService->shouldReceive('isIpAllowed')
        ->once()
        ->andReturn(false);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))
        ->getInstitutionalHome($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('start'));
});

test('getTerminalView renders TerminalView Inertia page when IP is allowed', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);
    $request = Mockery::mock(ResourceGroupRouteRequest::class);

    $request->shouldReceive('institutionSlug')->once()->andReturn($institution->slug);
    $request->shouldReceive('resourceGroupSlug')->once()->andReturn($resourceGroup->slug);
    $request->shouldReceive('ip')->once()->andReturn('127.0.0.1');

    $resolver->shouldReceive('resolve')
        ->once()
        ->andReturn($resourceGroup);

    $accessService->shouldReceive('isIpAllowed')->once()->andReturn(true);

    $builder->shouldReceive('buildTerminalViewData')
        ->once()
        ->with($resourceGroup)
        ->andReturn(['resourceGroup' => $resourceGroup]);

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))
        ->getTerminalView($request);

    expect($response)->toBeInstanceOf(InertiaResponse::class);
});

test('switchLanguage queues the requested locale', function (): void {
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $localeManager->shouldReceive('queue')->once()->with('de');

    $request = Mockery::mock(SwitchLanguageRequest::class);
    $request->shouldReceive('locale')->once()->andReturn('de');

    (new HomeController(
        Mockery::mock(HomePageDataBuilder::class),
        Mockery::mock(InstitutionAccessService::class),
        $localeManager,
        Mockery::mock(RouteResourceGroupResolver::class),
    ))->switchLanguage($request);
});
