<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResourceGroupRouteRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Http\CurrentUserStatusBuilder;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\InstitutionAccessService;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\LoginAction;
use App\Services\Http\LogoutAction;
use App\Services\Http\RouteResourceGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(
    LoginController::class,
    LoginAction::class
);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('home controller static pages return inertia responses', function (): void {
    $controller = new HomeController(
        Mockery::mock(HomePageDataBuilder::class),
        Mockery::mock(InstitutionAccessService::class),
        Mockery::mock(LocalePreferenceManager::class),
        Mockery::mock(RouteResourceGroupResolver::class),
    );

    expect($controller->getPrivacyStatement())->toBeInstanceOf(InertiaResponse::class)
        ->and($controller->getSiteCredits())->toBeInstanceOf(InertiaResponse::class);
});

test('home controller redirects blocked terminal views back to the start page', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $builder = Mockery::mock(HomePageDataBuilder::class);
    $accessService = Mockery::mock(InstitutionAccessService::class);
    $localeManager = Mockery::mock(LocalePreferenceManager::class);
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);
    $request = Mockery::mock(ResourceGroupRouteRequest::class);

    $request->shouldReceive('institutionSlug')->once()->andReturn($institution->slug);
    $request->shouldReceive('resourceGroupSlug')->once()->andReturn($resourceGroup->slug);
    $request->shouldReceive('ip')->once()->andReturn('10.0.0.1');

    $resolver->shouldReceive('resolve')
        ->once()
        ->with($institution->slug, $resourceGroup->slug, ['institution.settings', 'institution.week_days', 'settings'])
        ->andReturn($resourceGroup);

    $accessService->shouldReceive('isIpAllowed')
        ->once()
        ->with(
            Mockery::on(
                fn ($resolvedInstitution): bool => $resolvedInstitution instanceof Institution && $resolvedInstitution->is($institution),
            ),
            '10.0.0.1',
        )
        ->andReturnFalse();

    $response = (new HomeController($builder, $accessService, $localeManager, $resolver))
        ->getTerminalView($request);

    expect($response)->toBeInstanceOf(RedirectResponse::class)
        ->and($response->getTargetUrl())->toBe(route('start'));
});

test('login controller returns the public auth error response for invalid credentials', function (): void {
    $statusBuilder = Mockery::mock(CurrentUserStatusBuilder::class);
    $loginAction = Mockery::mock(LoginAction::class);
    $logoutAction = Mockery::mock(LogoutAction::class);
    $request = buildFormRequest(LoginRequest::class, [
        'username' => 'missing.user',
        'password' => 'wrong-secret',
    ]);

    $statusBuilder->shouldReceive('build')->never();
    $loginAction->shouldReceive('execute')
        ->once()
        ->with($request, [
            'username' => 'missing.user',
            'password' => 'wrong-secret',
        ])
        ->andReturnNull();

    $response = (new LoginController($statusBuilder, $loginAction, $logoutAction))->login($request);

    expect($response->getStatusCode())->toBe(401)
        ->and($response->getData(true))->toBe([
            'message' => __('auth.errors.user_not_found'),
        ]);
});
