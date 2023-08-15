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

        // FIXME: We dont know why we need ->withoutRelations() here ...
        $output = $institution->withoutRelations()->toArray();
        $output['settings'] = $settings;

        return Inertia::render('Home', [
            'institution' => $output,
            'is_multi_tenancy' => Institution::active()->count() > 1,
        ]);
    }

    public function getPrivacyStatement(): Response|RedirectResponse
    {
        return Inertia::render('PrivacyStatement');
    }

    public function switchLanguage(Request $request)
    {
        app()->setLocale($request->language);
        Cookie::queue('locale', $request->language, 600);
    }

    // FIXME
    public function checkLocaleCookie()
    {
        if (Cookie::has('locale')) {
            app()->setLocale(Cookie::get('locale'));
        } else {
            Cookie::queue('locale', env('APP_LOCALE'), 600);
        }
    }
}
