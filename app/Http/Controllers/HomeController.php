<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function getStart(): Response|RedirectResponse
    {
        // FIXME: output
        $institutions = Institution::active()->get();

        if ($institutions->count() == 1) {
            return redirect()->route('home', $institutions->first()->slug);
        }

        return Inertia::render('Start', [
            'institutions' => $institutions
        ]);
    }

    public function getInstitutionalHome(string $slug): Response|RedirectResponse
    {
        $institution = Institution::where('slug', $slug)->first();

        if (!$institution) {
            return redirect()->route('start');
        }

        $settings = [];
        foreach ($institution->settings as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        $output = $institution->toArray();
        $output['settings'] = $settings;
        $output['hiddenDays'] = $institution->getHiddenDays();

        return Inertia::render('Home', [
            'institution' => $output,
            'isMultiTenancy' => Institution::active()->count() > 1,
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

    public function getTerminalView(string $slug): Response
    {
        $institution = Institution::where('slug', $slug)->first();

        $settings = [];
        foreach ($institution->settings as $setting) {
            $settings[$setting->key] = $setting->value;
        }

        $output = $institution->withoutRelations()->toArray();
        $output['settings'] = $settings;
        $output['hiddenDays'] = $institution->getHiddenDays();

        return Inertia::render('TerminalView', [
            'institution' => $output,
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
