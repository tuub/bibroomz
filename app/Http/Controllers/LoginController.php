<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = [
            'username' => $request->username,
            'password' => $request->password,
        ];

        $auth = Auth::attempt($credentials);

        if ($auth) {
            $response = [
                'message' => 'Successfully logged in',
                'user' => [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ]
            ];

            return response()->json($response, Response::HTTP_OK);
        }

        $response = [
            'message' => 'Unauthorized',
        ];

        return response()->json($response, Response::HTTP_UNAUTHORIZED);
    }

    public function logout()
    {
        Auth::logout();

        //return redirect()->route('home');
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function check(Request $request)
    {
        $response = [
            'status' => auth()->check(),
            'user' => [
                'id' => auth()->user()->id ?? null,
                'name' => auth()->user()->name ?? null,
                'email' => auth()->user()->email ?? null,
            ]
        ];
        return response()->json($response);
    }
}
