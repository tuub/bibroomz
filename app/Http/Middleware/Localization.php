<?php

namespace App\Http\Middleware;

use App\Services\Http\LocalePreferenceManager;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        app(LocalePreferenceManager::class)->applyFromRequest($request);

        return $next($request);
    }
}
