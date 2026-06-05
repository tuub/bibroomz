<?php

namespace App\Services\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocalePreferenceManager
{
    public function applyFromRequest(Request $request): void
    {
        $locale = $request->cookie('locale');

        if (is_string($locale)) {
            app()->setLocale($locale);

            return;
        }

        Cookie::queue('locale', app()->getLocale(), 600);
    }

    public function queue(string $locale): void
    {
        Cookie::queue('locale', $locale, 600);
    }
}
