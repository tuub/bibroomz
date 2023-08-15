<?php

namespace App\Http\Controllers\Admin;

use App\Events\HappeningVerified;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HappeningController extends Controller
{
    public function getHappenings()
    {
        /** @var User */
        $user = auth()->user();

        $happenings = Happening::with(['resource', 'user1', 'user2'])->get()
            ->filter(fn ($happening) => $user->can('edit', $happening->resource->institution));

        return Inertia::render('Admin/Happenings/Index', [
            'happenings' => $happenings,
        ]);
    }

    public function createHappening()
    {
        return Inertia::render('Admin/Happenings/Form');
    }

    public function storeHappening(StoreHappeningRequest $request)
    {
        $resource = Resource::findOrFail($request->resource_id);
        Institution::abortIfUnauthorized($resource->institution);

        $sanitized = $request->sanitized()->merge(['reserved_at' => Carbon::now()]);
        $happening = Happening::create($sanitized->toArray());

        $happening->broadcast(HappeningCreated::class);

        return redirect()->route('admin.happening.index');
    }

    public function editHappening(Request $request)
    {
        $happening = Happening::with('resource')->findOrFail($request->id);
        Institution::abortIfUnauthorized($happening->resource->institution);

        $happening->start_date = Carbon::parse($happening->start)->format('d.m.Y');
        $happening->start_time = Carbon::parse($happening->start)->format('H:i');
        $happening->end_date = Carbon::parse($happening->end)->format('d.m.Y');
        $happening->end_time = Carbon::parse($happening->end)->format('H:i');

        return Inertia::render('Admin/Happenings/Form', $happening);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        // Check both old and new resource for authorization
        $happening = Happening::findOrFail($request->id);
        Institution::abortIfUnauthorized($happening->resource->institution);

        $resource = Resource::findOrFail($request->resource_id);
        Institution::abortIfUnauthorized($resource->institution);

        $sanitized = $request->sanitized();
        $happening->update($sanitized->toArray());

        $happening->broadcast(HappeningUpdated::class);

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        Institution::abortIfUnauthorized($happening->resource->institution);

        $happening->delete();

        $happening->broadcast(HappeningDeleted::class);

        return redirect()->route('admin.happening.index');
    }
}
