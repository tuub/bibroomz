<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Cookie::has('locale')) {
            Log::debug('Get Locale from Cookie: ' . Cookie::get('locale'));

            app()->setLocale(Cookie::get('locale'));
        } else {
            Log::debug('Set Locale Cookie: ' . app()->getLocale());

            Cookie::queue('locale', app()->getLocale(), 600);
        }

        return $next($request);
    }
}
