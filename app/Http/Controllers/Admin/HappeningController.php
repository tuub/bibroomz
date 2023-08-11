<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Arr;

class HappeningController extends Controller
{
    public function getHappenings()
    {
        return Inertia::render('Admin/Happenings/Index', [
            'happenings' => Happening::with(['resource', 'user1'])->get()
        ]);
    }

    public function createHappening()
    {
        return Inertia::render('Admin/Happenings/Form');
    }

    public function storeHappening(StoreHappeningRequest $request)
    {
        $validated = $request->validated();
        $sanitized = self::sanitizeHappeningData(
            [
                ...$validated,
                'reserved_at' => Carbon::now(),
            ]
        );
        Happening::create($sanitized);

        return redirect()->route('admin.happening.index');
    }

    public function editHappening(Request $request)
    {
        $happening = Happening::with('resource')->findOrFail($request->id);

        $happening->start_date = Carbon::parse($happening->start)->format('d.m.Y');
        $happening->start_time = Carbon::parse($happening->start)->format('H:i');
        $happening->end_date = Carbon::parse($happening->end)->format('d.m.Y');
        $happening->end_time = Carbon::parse($happening->end)->format('H:i');

        return Inertia::render('Admin/Happenings/Form', $happening);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        $happening = Happening::findOrFail($request->id);

        $validated = $request->validated();
        $sanitized = self::sanitizeHappeningData($validated);

        $happening->update($sanitized);

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        $happening->delete();

        return redirect()->route('admin.happening.index');
    }

    public static function sanitizeHappeningData($happening_data): array
    {
        $happening_data['start'] = Utility::createCarbonDateTime(
            $happening_data['start_date'],
            $happening_data['start_time']
        )->toISOString();
        $happening_data['end'] = Utility::createCarbonDateTime(
            $happening_data['end_date'],
            $happening_data['end_time']
        )->toIsoString();

        return Arr::except($happening_data, [
            'start_date',
            'start_time',
            'end_date',
            'end_time'
        ]);
    }
}
