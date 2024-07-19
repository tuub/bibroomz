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

        $date = CarbonImmutable::parse($request->date)->startOfDay();

        $resources = Resource::active()
            ->where('resource_group_id', $resource_group->id)
            ->orderBy('title')
            ->paginate($request->count)
            ->withPath($request->url() . '?count=' . $request->count . '&date=' . $date->format('Y-m-d'));

        foreach ($resources as $resource) {
            $business_hours = $resource->getBusinessHoursForDate($date)->map(
                fn ($business_hour) => [
                    'startTime' => $business_hour->start,
                    'endTime' => $business_hour->end,
                    'daysOfWeek' => $business_hour->week_days()->get()->pluck('day_of_week'),
                ]
            );

            if ($business_hours->isEmpty()) {
                $business_hours->push([
                    'daysOfWeek' => [],
                ]);
            }

            $output['resources'][] = [
                'id' => $resource->id,
                'title' => $resource->title,
                'businessHours' => $business_hours->values(),
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
