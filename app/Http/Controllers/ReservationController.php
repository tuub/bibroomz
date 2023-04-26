<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function getReservations(Request $request)
    {
        $output = [];
        $from = Carbon::parse($request->start);
        $to = Carbon::parse($request->end);

        $reservations = Reservation::with('resource', 'user1', 'user2')
            ->where('start', '>=', $from)
            ->where('end', '<=', $to)
            ->get();

        foreach ($reservations as $reservation) {
            //dd($reservation);

            $status = $reservation->getStatus();
            $style = $status['css'];
            $user = $status['user'];

            $output[] = [
                'id' => $reservation->id,
                'status' => $status, //$event['status'],
                'resourceId' => $reservation->resource->id,
                'start' => Carbon::parse($reservation->start)->format('Y-m-d H:i'),
                'end' => Carbon::parse($reservation->end)->format('Y-m-d H:i'),
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

    public function addReservation(Request $request)
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

            $reservation = new Reservation([
                'user_id_01' => auth()->user()->id,
                'user_id_02' => $as_admin ? auth()->user()->id : null,
                'resource_id' => $resource->id,
                'is_confirmed' => $as_admin ?? false,
                'confirmer' => $request->confirmer,
                'start' => self::carbonize($request->start)->format('Y-m-d H:i:s'),
                'end' => self::carbonize($request->end)->format('Y-m-d H:i:s'),
                'reserved_at' => Carbon::now(),
                'booked_at' => $as_admin ? Carbon::now() : null,
            ]);

            $op = $reservation->save() && $reservation->resource()->associate($resource);

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
