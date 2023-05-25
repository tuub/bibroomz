<?php

namespace App\Http\Controllers;

use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningsChanged;
use App\Events\HappeningUpdated;
use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HappeningController extends Controller
{
    public function getHappenings(Request $request): JsonResponse
    {
        $output = [];
        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        $log['PAYLOAD'] = json_encode(['from' => $from, 'to' => $to]);

        $happenings = Happening::with('resource', 'user1', 'user2')
            ->where('start', '>=', $from)
            ->where('end', '<=', $to)
            ->get();

        $log['RESULT COUNT'] = $happenings->count();

        foreach ($happenings as $happening) {
            $status = $happening->getStatus();
            $style = $status['css'];
            $user = $status['user'];

            // Note: resourceId and classNames have to be camelcased for FullCalendar recognition!
            $output[] = [
                'id' => $happening->id,
                'status' => $status, //$event['status'],
                'resourceId' => $happening->resource->id,
                'start' => Carbon::parse($happening->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($happening->end)->format('Y-m-d H:i'),
                'user' => $user,
                'classNames' => $style,
            ];
        }

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
            broadcast(new HappeningCreated($happening));
            broadcast(new HappeningsChanged());
        }

        // FIXME: GET THE SESSION FLASH MESSAGE TO FRONTEND!
        // BROADCAST?
        // WHAT TO DO WITH NON INERTIA CALLS?
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

        // Compile happening payload
        $happeningData = [
            'start' => Utility::carbonize($validated['start'])->format('Y-m-d H:i:s'),
            'end' => Utility::carbonize($validated['end'])->format('Y-m-d H:i:s'),
            'confirmer' => $validated['confirmer'],
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
            broadcast(new HappeningUpdated($happening));
            broadcast(new HappeningsChanged());
        }

        // FIXME: GET THE SESSION FLASH MESSAGE TO FRONTEND!
        // BROADCAST?
        // WHAT TO DO WITH NON INERTIA CALLS?
    }

    public function deleteHappening($id)
    {
        // Get happening object
        $happening = auth()->user()->happenings()->findOrFail($id);

        // Get users for private broadcast channels
        $user1 = $happening->user1;
        $user2 = $happening->user2;

        // Delete happening
        $op = $happening->delete();

        if ($op) {
            // Send broadcast events to frontend
            broadcast(new HappeningDeleted($id, $user1, $user2));
            broadcast(new HappeningsChanged());
        }
    }

    public function getTimeSlots(Request $request): JsonResponse
    {
        // sleep(1);
        $time_slots = [];

        $time_slots['start'] = Happening::getAvailableStartTimeSlots($request->resource_id, $request->start, $request->end);
        $time_slots['end'] = Happening::getAvailableEndTimeSlots($request->resource_id, $request->start, $request->end);
        $time_slots['start_selected'] = Utility::carbonize($request->start)->toIso8601String();

        $is_initial = filter_var($request->is_initial, FILTER_VALIDATE_BOOLEAN);
        $is_end_value_present = !empty(array_search(Utility::carbonize($request->end)->toIso8601String(), array_values($time_slots['end']),true));

        if ($is_initial === true || $is_end_value_present) {
            $time_slots['end_selected'] = Utility::carbonize($request->end)->toIso8601String();
        } else {
            $time_slots['end_selected'] = array_values($time_slots['end'])[0];
        }

        return response()->json($time_slots);
    }
}
