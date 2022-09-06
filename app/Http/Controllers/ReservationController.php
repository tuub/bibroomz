<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function addReservation(Request $request)
    {
        if (auth()->user())
        {
            return response()->json($request);
        }

        return response()->json('LOGIN FIRST');
    }
}
