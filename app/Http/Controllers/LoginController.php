<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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
                // 'message' => __('auth.login.success'),
                'admin' => auth()->user()->is_admin ?? false,
                'user' => [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ]
            ];

            return response()->json($response, Response::HTTP_OK);
        }

        $response = [
            // 'message' => __('auth.login.error'),
            'message' => __('auth.errors.user_not_found'),
        ];

        return response()->json($response, Response::HTTP_UNAUTHORIZED);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return response('', status: Response::HTTP_OK);

        // return response()->json(['message' => __('auth.logout.success')]);
    }

    public function check(Request $request)
    {
        $response = [
            'status' => auth()->check(),
            'admin' => auth()->user()->is_admin ?? false,
            'user' => [
                'id' => auth()->user()->id ?? null,
                'name' => auth()->user()->name ?? null,
                'email' => auth()->user()->email ?? null,
            ]
        ];
        return response()->json($response);
    }
}
