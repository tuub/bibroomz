<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // FIXME
        $institution = Institution::first();
        $settings = $institution->settings;
        //dd($settings);
        return Inertia::render('App', [
            'settings' => $settings
        ]);
    }
}
