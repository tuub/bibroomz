<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
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

    public function getUserHappenings()
    {
        $output = [];
        $happenings = Happening::with('resource')->whereHas('resource', function ($query) {
            $query->active();
        })
            ->where('user_id_01', auth()->user()->getKey())
            ->orWhere('user_id_02', auth()->user()->getKey())
            ->orWhere('confirmer', auth()->user()->name)
            ->current()
            ->orderBy('start')
            ->get();



        foreach ($happenings as $happening) {
            $output[] = [
                'id' => $happening->id,
                'user_01' => User::find($happening->user_id_01)->name,
                'user_02' => User::find($happening->user_id_02)->name ?? $happening->confirmer,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'can' => $happening->getPermissions(auth()->user()),
                'is_confirmed'=> $happening->is_confirmed,
                'resource' => [
                    'id' => $happening->resource_id,
                    'title' => $happening->resource->title,
                    'location' => $happening->resource->location,
                ],
                'reserved_at' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'confirmed_at' => Carbon::parse($happening->confirmed_at)->format('Y-m-d H:i'),
            ];
        }

        return $output;
    }
}
