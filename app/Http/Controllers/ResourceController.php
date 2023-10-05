<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public static function getResources(Request $request): JsonResponse
    {
        $output = [];

        $resource_group = ResourceGroup::whereHas(
            'institution',
            fn ($query) => $query->where('slug', $request->institution_slug),
        )->where('slug', $request->resource_group_slug)->firstOrFail();

        $resources = Resource::active()
            ->where('resource_group_id', $resource_group->id)
            ->paginate($request->count)
            ->withPath('/' . $request->path() . '?count=' . $request->count);

        foreach ($resources as $resource) {
            $business_hours = [];
            foreach ($resource->business_hours as $business_hour) {
                $days_of_week = $business_hour->week_days()->get()->pluck('day_of_week')->toArray();
                $business_hours[] = [
                    'startTime' => $business_hour->start,
                    'endTime' => $business_hour->end,
                    'daysOfWeek' => $days_of_week,
                ];
            }

            $output['resources'][] = [
                'id' => $resource->id,
                'title' => $resource->title,
                'businessHours' => $business_hours,
                'isVerificationRequired' => $resource->is_verification_required,
                'capacity' => $resource->capacity,
                'location_uri' => $resource->location_uri,
                'translations' => [
                    'title' => $resource->getTranslations('title'),
                    'description' => $resource->getTranslations('description'),
                    'location' => $resource->getTranslations('location'),
                    'resourceGroup' => $resource_group->getTranslations('term_singular'),
                ],
            ];
        }

        $output['pagination'] = [
            'previousPage' => $resources->previousPageUrl(),
            'nextPage' => $resources->nextPageUrl(),
        ];

        return response()->json(
            $output
        );
    }

    public function getTimeSlots(Request $request)
    {
        $resource = Resource::find($request->id);
        $happening = Happening::find($request?->happening_id);

        $start = CarbonImmutable::parse($request->start);
        $end = CarbonImmutable::parse($request->end);

        return $resource->getTimeSlots($start, $end, $happening);
    }
}
