<?php

namespace App\Services\Http;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginAction
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function execute(Request $request, array $credentials): ?User
    {
        if (! Auth::attempt($credentials)) {
            return null;
        }

        $request->session()->regenerate();

        $user = Auth::user();
        abort_unless($user instanceof User, 500);

        $user->update([
            'is_logged_in' => true,
        ]);

        return $user->refresh();
    }
}
