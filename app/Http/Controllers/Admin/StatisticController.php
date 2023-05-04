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

    public function editUser(Request $request)
    {
        $user = User::find($request->id);
        return Inertia::render('Admin/Users/Form', $user);
    }

    public function updateUser(Request $request)
    {
        $user = User::find($request->id);

        // Validate
        $attributes = $request->validate([
            'name' => ['required'],
            'email' => ['required', 'email'],
        ]);
        // Update
        $user->update($attributes);
        // Redirect
        return redirect('/admin/users');
    }
}
