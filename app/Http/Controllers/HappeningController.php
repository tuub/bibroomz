<?php

namespace App\Http\Controllers;

use App\Events\HappeningConfirmed;
use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningsChanged;
use App\Events\HappeningUpdated;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HappeningController extends Controller
{
    public function getHappenings(Request $request): JsonResponse
    {
        $output = [];

        // FIXME
        $institution = Institution::active()->first();

        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        $log['PAYLOAD'] = json_encode(['from' => $from, 'to' => $to]);

        $institution_resources = $institution->resources()->pluck('id')->all();

        $happenings = Happening::with('resource', 'user1', 'user2')
            ->whereDate('start', '>=', $from)
            ->whereDate('end', '<=', $to)
            ->whereIn('resource_id', $institution_resources)
            ->get();

        $log['RESULT COUNT'] = $happenings->count();

        foreach ($happenings as $happening) {
            $status = $happening->getStatus();

            // Note: resourceId and classNames have to be camelCased for FullCalendar recognition!
            $output[] = [
                'id' => $happening->id,
                'status' => $status,
                'resourceId' => $happening->resource->id,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'classNames' => $status['type'],
                'can' => $happening->getPermissions(auth()->user()),
            ];
        }

        // Since FullCalendar does not support closings as entities we have to include them
        // as happenings (sic!). We do it here for now until we find a better solution or
        // do it in the frontend.
        $institution_closings = $institution->closings;
        $institution_resources = $institution->resources;

        foreach ($institution_closings as $closing) {
            if ($closing->end->isAfter($from) && $closing->start->isBefore($to)) {
                foreach ($institution_resources as $resource) {
                    $output[] = [
                        'id' => $closing->id,
                        'status' => NULL,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->description,
                        'user' => NULL,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                }
            }
        }

        foreach ($institution_resources as $resource) {
            foreach ($resource->closings as $closing) {
                if ($closing->end->isAfter($from) && $closing->start->isBefore($to)) {
                    $output[] = [
                        'id' => $closing->id,
                        'status' => NULL,
                        'resourceId' => $resource->id,
                        'start' => Carbon::parse($closing->start)->format('Y-m-d H:i'),
                        'end' => Carbon::parse($closing->end)->format('Y-m-d H:i'),
                        'description' => $closing->description,
                        'user' => NULL,
                        'classNames' => 'closed',
                        'display' => 'background',
                    ];
                }
            }
        }

        $log['OUTPUT'] = $output;

        Utility::sendToLog('happenings', $log);

        return response()->json($output);
    }

    public function addHappening(AddHappeningRequest $request)
    {
        // Check auth -> do we need this?
        if (!auth()->user()) {
            return redirect()->back()->with('error', 'No session available.');
        }

        // Validate input
        $validated = $request->validated();

        // Query policy
        if (auth()->user()->cannot('create', Happening::class)) {
            abort(401, 'You are not allowed to create.');
        }

        // Get resource object
        $resource = Resource::find($validated['resource']['id']);
        $log['RESOURCE'] = $resource['title'];

        // Compile happening payload
        $happeningData = [
            'user_id_01' => auth()->user()->id,
            'user_id_02' => auth()->user()->is_admin ? auth()->user()->id : null,
            'resource_id' => $resource['id'],
            'is_confirmed' => auth()->user()->is_admin ?? false,
            'confirmer' => $validated['confirmer'],
            'start' => Utility::carbonize($validated['start'])->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($validated['end'])->format('Y-m-d H:i:s'),
            'reserved_at' => Carbon::now(),
            'confirmed_at' => auth()->user()->is_admin ? Carbon::now() : null,
        ];
        $log['PAYLOAD'] = json_encode($happeningData);

        // Create happening & relation
        $happening = new Happening($happeningData);
        $op = $happening->save() && $happening->resource()->associate($resource);
        $log['RESULT'] = json_encode($happening);

        if ($op) {
            // Write log record
            Utility::sendToLog('happenings', $log);

            // Send broadcast events to frontend
            $user1 = $happening->user1;
            $user2 = $happening->user2 ?? User::where('name', $happening->confirmer)->first();
            broadcast(new HappeningCreated($happening, $user1));
            if ($user2) {
                broadcast(new HappeningCreated($happening, $user2));
            }
            broadcast(new HappeningsChanged());
        }
    }

    public function updateHappening(UpdateHappeningRequest $request)
    {
        // Check auth -> do we need this?
        if (!auth()->user()) {
            return redirect()->back()->with('error', 'No session available.');
        }

        // Validate input
        $validated = $request->validated();

        // Get happening object
        $happening = auth()->user()->happenings()->findOrFail($validated['id']);
        $log['HAPPENING'] = $happening;

        // Query policy
        if (auth()->user()->cannot('update', $happening)) {
            abort(401, 'You are not allowed to update.');
        }

        // Compile happening payload
        $happeningData = [
            'start' => Utility::carbonize($validated['start'])->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($validated['end'])->format('Y-m-d H:i:s'),
            // 'confirmer' => $validated['confirmer'],
        ];
        $log['PAYLOAD'] = json_encode($happeningData);

        // Update happening & relation
        $op = $happening->update($happeningData) && $happening->resource()->associate($validated['id']);

        if ($op) {
            // Get just updated relation object for broadcasting back
            $happening = Happening::with('resource')->find($happening->id);
            $log['RESULT'] = json_encode($happening);

            // Write log record
            Utility::sendToLog('happenings', $log);

            // Send broadcast events to frontend
            $user1 = $happening->user1;
            $user2 = $happening->user2 ?? User::where('name', $happening->confirmer)->first();
            broadcast(new HappeningUpdated($happening, $user1));
            if ($user2) {
                broadcast(new HappeningUpdated($happening, $user2));
            }
            broadcast(new HappeningsChanged());
        }
    }

    public function confirmHappening($id)
    {
        // Get happening object
        $happening = Happening::findOrFail($id);

        // Query policy
        if (auth()->user()->cannot('confirm', $happening)) {
            abort(401, 'You are not allowed to confirm.');
        }

        $op = $happening->update([
            'user_id_02' => auth()->user()->getKey(),
            'is_confirmed' => true,
            'confirmer' => null,
        ]);

        if ($op) {
            // Get just updated relation object for broadcasting back
            $happening = Happening::with('resource')->find($happening->id);
            $log['RESULT'] = json_encode($happening);

            // Write log record
            Utility::sendToLog('happenings', $log);

            // Send broadcast events to frontend
            broadcast(new HappeningConfirmed($happening, $happening->user1));
            broadcast(new HappeningConfirmed($happening, $happening->user2));
            broadcast(new HappeningsChanged());
        }
    }

    public function deleteHappening($id)
    {
        // Get happening object
        $happening = auth()->user()->happenings()->findOrFail($id);

        // Query policy
        if (auth()->user()->cannot('delete', $happening)) {
            abort(401, 'You are not allowed to delete.');
        }

        // Get users for private broadcast channels
        $user1 = $happening->user1;
        $user2 = $happening->user2 ?? User::where('name', $happening->confirmer)->first();


        // Delete happening
        $op = $happening->delete();

        if ($op) {
            // Send broadcast events to frontend
            broadcast(new HappeningDeleted($id, $user1, $user2));
            broadcast(new HappeningsChanged());
        }
    }
}
