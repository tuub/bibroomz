<?php

namespace App\Http\Controllers;

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
            //dd($happening);

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

            $happening = new Happening([
                'user_id_01' => auth()->user()->id,
                'user_id_02' => $as_admin ? auth()->user()->id : null,
                'resource_id' => $resource->id,
                'is_confirmed' => $as_admin ?? false,
                'confirmer' => $request->confirmer,
                'start' => self::carbonize($request->start)->format('Y-m-d H:i:s'),
                'end' => self::carbonize($request->end)->format('Y-m-d H:i:s'),
                'reserved_at' => Carbon::now(),
                'confirmed_at' => $as_admin ? Carbon::now() : null,
            ]);

            $op = $happening->save() && $happening->resource()->associate($resource);

            if ($op) {
                return 'SUCCESS!';
            }

            return 'FAILURE!';
            /*
            return redirect()->back()->withErrors([
                'create' => 'ups, there was an error']
            );
            */
        }

        //return Inertia::render('Profile');
        return 'FAILURE!';
        # return response()->json('NOPE! NOT LOGGED IN!', 403);
    }
}
