<?php

namespace App\Http\Controllers;

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
        $institutions = Institution::with('resource_groups')->active()->get();
        $resource_groups = ResourceGroup::active()->get();

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
        $resource_group = ResourceGroup::whereHas(
            'institution',
            fn ($query) => $query->where('slug', $request->institution_slug)
        )->where('slug', $request->resource_group_slug)->firstOrFail();

        if (!$resource_group) {
            return redirect()->route('start');
        }

        $settings = [];
        foreach ($resource_group->institution->settings as $setting) {
            $settings['institution'][$setting->key] = $setting->value;
        }
        foreach ($resource_group->settings as $setting) {
            $settings['resource_group'][$setting->key] = $setting->value;
        }

        return Inertia::render('Home', [
            'resourceGroup' => $resource_group,
            'settings' => $settings,
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
        $resource_group = ResourceGroup::whereHas(
            'institution',
            fn ($query) => $query->where('slug', $request->institution_slug)
        )->where('slug', $request->resource_group_slug)->firstOrFail();

        $settings = [];
        foreach ($resource_group->institution->settings as $setting) {
            $settings['institution'][$setting->key] = $setting->value;
        }
        foreach ($resource_group->settings as $setting) {
            $settings['resource_group'][$setting->key] = $setting->value;
        }

        return Inertia::render('TerminalView', [
            'resourceGroup' => $resource_group,
            'settings' => $settings,
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
}
