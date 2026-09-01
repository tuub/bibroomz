<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImpersonateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function start(ImpersonateUserRequest $request): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $target = $request->targetUser();

        $request->session()->put('impersonator_id', $admin->getKey());
        Auth::guard('web')->login($target);

        return redirect()->route('start');
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->pull('impersonator_id');
        abort_unless(is_string($impersonatorId), 403);

        $admin = User::find($impersonatorId);
        abort_unless($admin instanceof User, 403);

        Auth::guard('web')->login($admin);

        return redirect()->route('admin.user.index');
    }
}
