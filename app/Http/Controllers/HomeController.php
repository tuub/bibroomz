<?php

namespace App\Http\Controllers;

use App\Library\IpChecker;
use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function getStart(): Response|RedirectResponse
    {
        $institutions = Institution::active()
            ->whereHas('resource_groups', fn ($q) => $q->active())
            ->with(['resource_groups' => fn ($q) => $q->active()])
            ->get();

        foreach ($institutions as $institution) {
            if (!$this->isIpAllowed($institution)) {
                $institutions = $institutions->reject(fn ($item) => $item->id == $institution->id);
            }
        }

        $resource_groups = $institutions->flatMap(fn ($institution) => $institution->resource_groups);

        if ($resource_groups->count() == 1) {
            return redirect()->route('home', [
                'institution_slug' => $resource_groups->first()->institution->slug,
                'resource_group_slug' => $resource_groups->first()->slug,
            ]);
        }

        return Inertia::render('Start', [
            'appName' => config('app.name'),
            'institutions' => $institutions,
        ]);
    }

    public function getInstitutionalHome(Request $request): Response|RedirectResponse
    {
        $resource_group = $this->getResourceGroupFromRequest($request);

        if (!$this->isIpAllowed($resource_group->institution)) {
            return redirect()->route('start');
        }

        return Inertia::render('Home', [
            'resourceGroup' => $resource_group,
            'settings' => self::mapSettings($resource_group),
            'hiddenDays' => $resource_group->institution->getHiddenDays(),
            'isMultiTenancy' => ResourceGroup::active()->count() > 1,
        ]);
    }

    public function getPrivacyStatement(): Response
    {
        return Inertia::render('PrivacyStatement');
    }

    public function getSiteCredits(): Response
    {
        return Inertia::render('SiteCredits');
    }

    public function getTerminalView(Request $request): Response
    {
        $resource_group = $this->getResourceGroupFromRequest($request);

        if (!$this->isIpAllowed($resource_group->institution)) {
            return redirect()->route('start');
        }

        return Inertia::render('TerminalView', [
            'resourceGroup' => $resource_group,
            'settings' => $this->mapSettings($resource_group),
            'hiddenDays' => $resource_group->institution->getHiddenDays(),
        ]);
    }

    public function switchLanguage(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|in:en,de',
        ]);

        $locale = $validated['locale'];

        Cookie::queue('locale', $locale, 600);
    }

    private function getResourceGroupFromRequest(Request $request): ResourceGroup
    {
        return ResourceGroup::whereHas(
            'institution',
            fn ($query) => $query->where('slug', $request->institution_slug)
        )->where('slug', $request->resource_group_slug)->firstOrFail();
    }

    private function mapSettings(ResourceGroup $resource_group): array
    {
        $settings = [];

        foreach ($resource_group->institution->settings as $setting) {
            $settings['institution'][$setting->key] = $setting->value;
        }

        foreach ($resource_group->settings as $setting) {
            $settings['resource_group'][$setting->key] = $setting->value;
        }

        return $settings;
    }

    private function isIpAllowed(Institution $institution): bool
    {
        $allowed_ips = explode(',', $institution->settings()->firstWhere('key', 'allowed_ips')?->value);

        return (new IpChecker($allowed_ips))->isIpAllowed(request()->ip());
    }
}
