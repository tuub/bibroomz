<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function getUserProfile()
    {
        return Inertia::render('Profile', [
            'time' => now()->toTimeString(),
            'user' => auth()->user(),
            'user_happenings' => auth()->user()->happenings,
        ]);
    }

    public function getUserHappenings(Request $request)
    {
        /** @var User */
        $user = auth()->user();

        $institution_id = $request->institution_id;

        $happenings = Happening::with('resource')
            ->whereHas('resource', fn (Builder $query) => $query->where('institution_id', $institution_id)->active())
            ->where(fn (Builder $query) => $query->where('user_id_01', $user->getKey())
                ->orWhere('user_id_02', $user->getKey())
                ->orWhere('verifier', $user->name))
            ->weekly()
            ->orderBy('start')
            ->get()
            ->map(function ($happening) {
                $start = CarbonImmutable::parse($happening->start);
                $end = CarbonImmutable::parse($happening->end);

                [$open, $start, $end] = $happening->resource->findOpen($start, $end);
                [$closed, $start, $end] = $happening->resource->findClosed($start, $end);

                $happening->start = $start;
                $happening->end = $end;

                return ($open && !$closed) ? $happening : null;
            })->filter(fn ($h) => $h);

        $output = [];
        foreach ($happenings as $happening) {
            $output[] = [
                'id' => $happening->id,
                'user_01' => User::find($happening->user_id_01)->name,
                'user_02' => User::find($happening->user_id_02)->name ?? $happening->verifier,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'can' => $happening->getPermissions(auth()->user()),
                'is_verified' => $happening->is_verified,
                'resource' => [
                    'id' => $happening->resource_id,
                    'title' => $happening->resource->title,
                    'location' => $happening->resource->location,
                    'institution_id' => $happening->resource->institution_id,
                ],
                'reserved_at' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'verified_at' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
                'isVerificationRequired' => $happening->resource->is_verification_required,
            ];
        }

        return $output;
    }
}
