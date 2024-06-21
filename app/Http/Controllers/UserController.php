<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserHappenings(Request $request)
    {
        $resource_group = ResourceGroup::find($request->resource_group_id);
        $user = auth()->user();

        $happenings = Happening::with('resource')
            ->resourceGroup($resource_group)
            ->user($user)
            ->active()
            ->weekly()
            ->orderBy('start')
            ->get()
            ->filter->isResourceOpen()
            ->map->withAdjustedStartEndTimes();

        return $happenings->map(function ($happening) {
            return [
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
        })->values();
    }
}
