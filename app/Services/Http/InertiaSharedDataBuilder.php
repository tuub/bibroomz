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

        return [
            'route' => $request->route()?->getName(),
            'auth' => $user ? [
                'user' => [
                    'name' => $user->name,
                ],
            ] : null,
            'systemNotification' => AppSetting::get('system_notification'),
        ];
    }
}
