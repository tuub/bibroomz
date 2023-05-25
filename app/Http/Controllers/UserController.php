<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function getUserProfile(Request $request)
    {
        return Inertia::render('Profile', [
            'time' => now()->toTimeString(),
            'user' => auth()->user(),
            'user_happenings' => auth()->user()->happenings,
        ]);
    }

    public function getUserHappenings(Request $request)
    {
        //dd(auth()->user()->happenings()->with('resource')->active()->orderBy('start')->get());
        return auth()->user()->happenings()->with('resource')->whereHas('resource', function ($query) {
            $query->active();
        })->current()->orderBy('start')->get();
    }
}
