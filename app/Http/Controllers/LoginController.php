<?php

namespace App\Http\Controllers;

use App\Exceptions\AlmaNoEmailException;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\Http\CurrentUserStatusBuilder;
use App\Services\Http\LoginAction;
use App\Services\Http\LogoutAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginController extends Controller
{
    public function __construct(
        private readonly CurrentUserStatusBuilder $currentUserStatusBuilder,
        private readonly LoginAction $loginAction,
        private readonly LogoutAction $logoutAction
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->loginAction->execute($request, $request->credentials());
        } catch (AlmaNoEmailException) {
            return response()->json(
                ['message' => __('auth.errors.no_email')],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        if (! $user instanceof User) {
            $response = [
                'message' => __('auth.errors.user_not_found'),
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

        $response = $this->currentUserStatusBuilder->build($user);

        return response()->json($response, Response::HTTP_OK);
    }

    public function logout(Request $request): Response
    {
        $this->logoutAction->execute($request);

        return response()->noContent();
    }

    public function check(): JsonResponse
    {
        if (! auth()->check()) {
            $response = [
                'message' => __('auth.errors.no_auth'),
            ];

            return response()->json($response, Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        $response = $this->currentUserStatusBuilder->build($user->refresh());

        return response()->json($response, Response::HTTP_OK);
    }
}
