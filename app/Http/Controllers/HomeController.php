<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResourceGroupRouteRequest;
use App\Http\Requests\SwitchLanguageRequest;
use App\Services\Http\HomePageDataBuilder;
use App\Services\Http\InstitutionAccessService;
use App\Services\Http\LocalePreferenceManager;
use App\Services\Http\RouteResourceGroupResolver;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomePageDataBuilder $homePageDataBuilder,
        private readonly InstitutionAccessService $institutionAccessService,
        private readonly LocalePreferenceManager $localePreferenceManager,
        private readonly RouteResourceGroupResolver $resourceGroupResolver
    ) {}

    public function getStart(): Response|RedirectResponse
    {
        $data = $this->homePageDataBuilder->buildStartPageData(request()->ip());
        $redirect = $data['redirect'] ?? null;

        if (is_array($redirect)) {
            $institutionSlug = $redirect['institution_slug'] ?? null;
            $resourceGroupSlug = $redirect['resource_group_slug'] ?? null;

            if (! is_string($institutionSlug) || ! is_string($resourceGroupSlug)) {
                return Inertia::render('Start', []);
            }

            return redirect()->route('home', [
                'institution_slug' => $institutionSlug,
                'resource_group_slug' => $resourceGroupSlug,
            ]);
        }

        $props = $data['props'] ?? [];

        return Inertia::render('Start', is_array($props) ? $props : []);
    }

    public function getInstitutionalHome(ResourceGroupRouteRequest $request): Response|RedirectResponse
    {
        $resourceGroup = $this->resourceGroupResolver->resolve(
            $request->institutionSlug(),
            $request->resourceGroupSlug(),
            ['institution.settings', 'institution.week_days', 'settings']
        );

        if (! $this->institutionAccessService->isIpAllowed($resourceGroup->institution, $request->ip())) {
            return redirect()->route('start');
        }

        return Inertia::render('Home', $this->homePageDataBuilder->buildHomePageData($resourceGroup));
    }

    public function getPrivacyStatement(): Response
    {
        return Inertia::render('PrivacyStatement');
    }

    public function getSiteCredits(): Response
    {
        return Inertia::render('SiteCredits');
    }

    public function getTerminalView(ResourceGroupRouteRequest $request): Response|RedirectResponse
    {
        $resourceGroup = $this->resourceGroupResolver->resolve(
            $request->institutionSlug(),
            $request->resourceGroupSlug(),
            ['institution.settings', 'institution.week_days', 'settings']
        );

        if (! $this->institutionAccessService->isIpAllowed($resourceGroup->institution, $request->ip())) {
            return redirect()->route('start');
        }

        return Inertia::render('TerminalView', $this->homePageDataBuilder->buildTerminalViewData($resourceGroup));
    }

    public function switchLanguage(SwitchLanguageRequest $request): void
    {
        $this->localePreferenceManager->queue($request->locale());
    }
}
