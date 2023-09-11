<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    private function userStatus()
    {
        $user = Auth::user();

        return [
            'isAdmin' => $user->isAdmin(),
            'user' => $user->only(['id', 'name', 'email']),
            'permissions' => $user->getPermissions(),
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

        Auth::user()->update([
            'is_logged_in' => true,
        ]);

        $response = $this->userStatus();
        return response()->json($response, Response::HTTP_OK);
    }

    public function logout()
    {
        $user = Auth::user();
        $user->update(['is_logged_in' => false]);

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
