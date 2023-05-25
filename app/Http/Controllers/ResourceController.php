<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public static function getResources(): \Illuminate\Http\JsonResponse
    {
        $output = [];

        // FIXME
        $institution = Institution::active()->first();
        $resources = Resource::active()->where('institution_id', $institution->id)->get();

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

            $output[] = [
                'id' => $resource->id,
                'title' => $resource->title,
                'businessHours' => $business_hours,
            ];
        }

        return response()->json($output);
    }

    public function getFormBusinessHours(Request $request)
    {
        $resource = Resource::find($request->id);
        $happening = Happening::find($request?->happening_id);

        $start = CarbonImmutable::parse($request->start);
        $end = CarbonImmutable::parse($request->end);

        return $resource->getFormBusinessHours($start, $end, $happening);
    }
}
