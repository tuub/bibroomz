<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function getStart(): Response|RedirectResponse
    {
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

        $settings = $institution->settings;

        return Inertia::render('Home', [
            'institution' => $institution,
            'settings' => $settings,
            'is_multi_tenancy' => Institution::active()->count() > 1,
        ]);
    }
}
