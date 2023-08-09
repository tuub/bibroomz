<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UserController extends Controller
{
    public static function getUsers()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with(['happenings'])->get()
        ]);
    }

    public function getFormUsers()
    {
        return User::get(['id', 'name']);
    }

    public function editUser(Request $request)
    {
        $user = User::find($request->id);
        $institutions = Institution::all()->map->only(['id', 'title', 'short_title']);

        return Inertia::render('Admin/Users/Form', [
            'user' => $user,
            'institutions' => $institutions,
            'institutionAdmin' => $user->institutionAdmin(),
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = User::find($request->id);

        $attributes = $request->validate([
            'is_admin' => ['required', 'boolean'],
        ]);

        $user->update($attributes);

        $institutions = collect($request->institution_admin)->filter(fn($is_admin) => $is_admin)->keys();
        $user->institutions()->sync($institutions);

        return redirect()->route('admin.user.index');
    }
}
