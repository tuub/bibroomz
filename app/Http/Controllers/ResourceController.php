<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public static function getResources(Request $request): \Illuminate\Http\JsonResponse
    {
        $output = Resource::active()->get(['id', 'title']);

        return response()->json($output);
    }

    public function getBusinessHours()
    {
        $businessHours = [
            // MONDAY
            1 => ['start' => '09:00', 'end' => '22:00'],
            // TUESDAY
            2 => ['start' => '09:00', 'end' => '22:00'],
            // WEDNESDAY
            3 => ['start' => '09:00', 'end' => '22:00'],
            // THURSDAY
            4 => ['start' => '09:00', 'end' => '22:00'],
            // FRIDAY
            5 => ['start' => '09:00', 'end' => '22:00'],
            // SATURDAY
            6 => ['start' => '10:00', 'end' => '18:00'],
            // SUNDAY
            0 => ['start' => null, 'end' => null]
        ];

        return json_encode($businessHours);
    }
}
