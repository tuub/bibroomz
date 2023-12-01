<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserHappenings(Request $request)
    {
        /** @var User */
        $user = auth()->user();

        $resource_group_id = $request->resource_group_id;

        $happenings = Happening::with('resource')
            ->whereHas(
                'resource',
                fn (Builder $query) => $query->where('resource_group_id', $resource_group_id)->active(),
            )
            ->where(fn (Builder $query) => $query->where('user_id_01', $user->getKey())
                ->orWhere('user_id_02', $user->getKey())
                ->orWhere('verifier', strtolower($user->name)))
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
                'isVerified' => $happening->is_verified,
                'resource' => [
                    'id' => $happening->resource_id,
                    'title' => $happening->resource->getTranslations('title'),
                    'capacity' => $happening->resource->capacity,
                    'location' => $happening->resource->getTranslations('location'),
                    'locationUri' => $happening->resource->location_uri,
                    'description' => $happening->resource->getTranslations('description'),
                    'resourceGroup' => $happening->resource->resource_group->getTranslations('term_singular'),
                    'institutionId' => $happening->resource->resource_group->institution_id,
                ],
                'reservedAt' => Carbon::parse($happening->reserved_at)->format('Y-m-d H:i'),
                'verifiedAt' => Carbon::parse($happening->verified_at)->format('Y-m-d H:i'),
                'isVerificationRequired' => $happening->resource->is_verification_required,
                'label' => $happening->getTranslations('label'),
            ];
        }

        return $output;
    }
}
