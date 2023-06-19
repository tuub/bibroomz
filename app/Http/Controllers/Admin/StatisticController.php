<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StatisticController extends Controller
{
    public static function getStats(Request $request)
    {
        return Inertia::render('Admin/Stats');
    }
}
