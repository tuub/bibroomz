<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getEvents(Request $request)
    {
        return auth()->user()->reservations()->with('resource')->orderBy('start')->get();
    }
}
