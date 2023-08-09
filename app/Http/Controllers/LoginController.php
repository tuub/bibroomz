<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    private function userStatus()
    {
        /** @var User */
        $user = auth()->user();

        return [
            'isAdmin' => $user->isAdmin(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'institutionAdmin' => $user->institutionAdmin(),
        ];
    }
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

        if (!$auth) {
            $response = [
                'message' => __('auth.errors.user_not_found'),
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

        $response = $this->userStatus();
        return response()->json($response, Response::HTTP_OK);
    }

    public function logout()
    {
        Auth::logout();

        return response()->noContent();
    }

    public function check()
    {
        if (!auth()->check()) {
            $response = [
                'message' => __('auth.errors.no_auth'),
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

        $response = $this->userStatus();
        return response()->json($response, Response::HTTP_OK);
    }
}
