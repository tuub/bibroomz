<?php

namespace App\Services\Http;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InertiaSharedDataBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Request $request): array
    {
        $user = Auth::user();
        $timezone = config('app.timezone');

        return [
            'route' => $request->route()?->getName(),
            // Datetimes travel without a zone, so the frontend has to be told
            // which one they are wall-clock values in - the browser's is its own.
            'timezone' => is_string($timezone) ? $timezone : 'UTC',
            'auth' => $user ? [
                'user' => [
                    'name' => $user->name,
                ],
            ] : null,
            'systemNotification' => AppSetting::get('system_notification'),
        ];
    }
}
