<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
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

    public function storeHappening(Request $request)
    {
        // Validate
        $attributes = $request->validate([
            'resource_id' => ['required'],
            'start' => 'required',
            'end' => 'required',
            'user_id_01' => 'required',
            'confirmer' => 'required',
        ]);

        $attributes['reserved_at'] = Carbon::now();
        // Create
        Happening::create($attributes);
        // Redirect
        return redirect('/admin/happenings');
    }

    public function editHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        return Inertia::render('Admin/Happenings/Form', $happening);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        $validated = $request->validated();

        $happening = Happening::find($request->id);#
        $happening->update($validated);

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        $happening->delete();

        return redirect()->route('admin.happening.index');
    }
}
