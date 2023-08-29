<?php

namespace App\Http\Controllers\Admin;

use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HappeningController extends Controller
{
    public function getHappenings()
    {
        $happenings = Happening::with(['resource', 'user1', 'user2'])->orderBy('start')->get()
            ->filter->isViewableByUser(auth()->user());

        return Inertia::render('Admin/Happenings/Index', [
            'happenings' => $happenings,
        ]);
    }

    public function createHappening()
    {
        $resources = Resource::active()->orderBy('title')->without('closings')->get()
            ->filter->isUserAbleToCreateHappening(auth()->user())->values();

        $users = User::all()->map(function ($user) {
            $user->permissions = $user->getPermissions(['no verifier']);

            return $user;
        });

        return Inertia::render('Admin/Happenings/Form', [
            'resources' => $resources->map->only(['id', 'title', 'institution_id', 'is_verification_required']),
            'users' => $users->map->only(['id', 'name', 'permissions']),
        ]);
    }

    public function storeHappening(StoreHappeningRequest $request)
    {
        $sanitized = $request->sanitized()->merge(['reserved_at' => Carbon::now()]);
        $happening = Happening::create($sanitized->toArray());

        $happening->broadcast(HappeningCreated::class);

        return redirect()->route('admin.happening.index');
    }

    public function editHappening(Request $request)
    {
        /** @var Happening */
        $happening = Happening::findOrFail($request->id);

        $this->authorize('adminUpdate', $happening);

        $happening->start_date = Carbon::parse($happening->start)->format('d.m.Y');
        $happening->start_time = Carbon::parse($happening->start)->format('H:i');
        $happening->end_date = Carbon::parse($happening->end)->format('d.m.Y');
        $happening->end_time = Carbon::parse($happening->end)->format('H:i');

        $resources = $happening->resource->institution->resources()
            ->active()->orderBy('title')->without('closings')->get();

        $users = User::all()->map(function ($user) {
            $user->permissions = $user->getPermissions(['no verifier']);

            return $user;
        });

        return Inertia::render('Admin/Happenings/Form', [
            'happening' => $happening->only([
                'id',
                'user_id_01',
                'user_id_02',
                'resource_id',
                'is_verified',
                'verifier',
                'start_date',
                'start_time',
                'end_date',
                'end_time',
            ]),
            'resources' => $resources->map->only(['id', 'title', 'institution_id', 'is_verification_required']),
            'users' => $users->map->only(['id', 'name', 'permissions']),
        ]);
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        $happening = Happening::findOrFail($request->id);
        $sanitized = $request->sanitized();
        $happening->update($sanitized->toArray());

        $happening->broadcast(HappeningUpdated::class);

        return redirect()->route('admin.happening.index');
    }

    public function deleteHappening(Request $request)
    {
        $happening = Happening::find($request->id);
        $this->authorize('adminDelete', $happening);
        $happening->delete();

        $happening->broadcast(HappeningDeleted::class);

        return redirect()->route('admin.happening.index');
    }
}
