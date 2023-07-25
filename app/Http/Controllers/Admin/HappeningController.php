<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HappeningController extends Controller
{
    public function getHappenings(Request $request)
    {
        return Inertia::render('Admin/Happenings/Index', [
            'happenings' => Happening::with(['resource', 'user1'])->get()
        ]);
    }

    public function createHappening(Request $request)
    {
        return Inertia::render('Admin/Happenings/Form');
    }

    public function storeHappening(StoreHappeningRequest $request)
    {
        // Create
        $happening_data = array_merge($request->all(), ['reserved_at' => Carbon::now()]);
        $happening = Happening::create($happening_data);

        // Redirect
        return redirect()->route('admin.happening.index');
    }

    public function editHappening(Request $request)
    {
        $happening = Happening::with('resource')->findOrFail($request->id);
        return Inertia::render('Admin/Happenings/Form', $happening);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        $happening = Happening::findOrFail($request->id);
        $happening->update($request->all());

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        $happening->delete();

        return redirect()->route('admin.happening.index');
    }
}
