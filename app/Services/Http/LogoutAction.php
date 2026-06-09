<?php

declare(strict_types=1);

namespace App\Services\Http;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutAction
{
    public function execute(Request $request): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->update([
                'is_logged_in' => false,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
