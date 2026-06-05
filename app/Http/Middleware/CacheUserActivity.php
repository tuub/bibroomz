<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Http\UserActivityRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User) {
            app(UserActivityRecorder::class)->record($user);
        }

        return $next($request);
    }
}
