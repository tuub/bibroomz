<?php

namespace App\Http\Controllers;

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public static function getResources(Request $request): JsonResponse
    {
        $output = [];

        $institution = Institution::where('slug', $request->slug)->first();

        if (!$institution) {
            abort(404);
        }

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
                'isVerificationRequired' => $resource->is_verification_required,
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
