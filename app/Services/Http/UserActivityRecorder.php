<?php

namespace App\Services\Http;

use App\Models\User;

class UserActivityRecorder
{
    public function record(User $user): void
    {
        $userKey = $user->getKey();

        if (! is_int($userKey) && ! is_string($userKey)) {
            return;
        }

        $key = 'user_activity_'.$userKey;
        $lifetime = config('session.lifetime');
        $ttl = now()->addMinutes(is_int($lifetime) ? $lifetime : 0);

        cache()->put($key, now(), $ttl);
    }
}
