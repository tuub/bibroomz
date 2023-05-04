<?php

namespace App\Http\Controllers;

use App\Events\HappeningCreated;
use App\Events\HappeningDeleted;
use App\Events\HappeningsChanged;
use App\Events\HappeningUpdated;
use App\Models\Happening;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HappeningController extends Controller
{
    public function getHappenings(Request $request)
    {
        $output = [];
        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        $happenings = Happening::with('resource', 'user1', 'user2')
            ->where('start', '>=', $from)
            ->where('end', '<=', $to)
            ->get();

        foreach ($happenings as $happening) {
            $status = $happening->getStatus();
            $style = $status['css'];
            $user = $status['user'];

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

        return response()->json($output);
    }

    public static function carbonize($date)
    {
        if (is_string($date)) {
            return Carbon::parse($date);
        }

        return $date;
    }

    public function addHappening(Request $request)
    {
        if (!auth()->user()) {
            return redirect()->back()->with('error', 'Updating the demo user is not allowed.');
        }
        if (auth()->user()) {
            $as_admin = false;

            $request->validate([
                'resource' => 'required',
                'start' => 'required',
                'end' => 'required',
                'confirmer' => 'required',
            ]);

            $resource = Resource::find($request->resource['id']);
            $happeningData = [
                'user_id_01' => auth()->user()->id,
                'user_id_02' => $as_admin ? auth()->user()->id : null,
                'resource_id' => $resource->id,
                'is_confirmed' => $as_admin ?? false,
                'confirmer' => $request->confirmer,
                'start' => self::carbonize($request->start)->format('Y-m-d H:i:s'),
                'end' => self::carbonize($request->end)->format('Y-m-d H:i:s'),
                'reserved_at' => Carbon::now(),
                'confirmed_at' => $as_admin ? Carbon::now() : null,
            ];

            $happening = Happening::create($happeningData);
            $op = $happening->save() && $happening->resource()->associate($resource);
            broadcast(new HappeningCreated($happening));
            broadcast(new HappeningsChanged());

            // FIXME: ADD SESSION FLASH HERE

            // FIXME: ADD SESSION FLASH HERE
        }
        return false;
        //return Inertia::render('Profile');
        //
        # return response()->json('NOPE! NOT LOGGED IN!', 403);
    }

    public function updateHappening(Request $request)
    {
        $request->validate([
            'start' => 'required',
            'end' => 'required',
            'confirmer' => 'required',
        ]);

        $happening = auth()->user()->happenings()->findOrFail($request->id);

        $happeningData = [
            'start' => self::carbonize($request->start)->format('Y-m-d H:i:s'),
            'end' => self::carbonize($request->end)->format('Y-m-d H:i:s'),
            'confirmer' => $request->confirmer,
        ];

        $op = $happening->update($happeningData) && $happening->resource()->associate($request->resource['id']);;
        $happening = Happening::with('resource')->find($happening->id);

        broadcast(new HappeningUpdated($happening));
        broadcast(new HappeningsChanged());
    }

    public function deleteHappening($id)
    {
        $user1 = Happening::find($id)->user1;
        $user2 = Happening::find($id)->user1;

        $op = auth()->user()->happenings()->findOrFail($id)->delete();

        if ($op) {
            broadcast(new HappeningDeleted($id, $user1, $user2));
            broadcast(new HappeningsChanged());
        }
    }
}
