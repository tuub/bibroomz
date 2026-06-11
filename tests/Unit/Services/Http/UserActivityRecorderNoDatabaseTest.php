<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Http\UserActivityRecorder;
use Illuminate\Support\Facades\Cache;

covers(UserActivityRecorder::class);

test('record returns early without writing cache when user key is null', function (): void {
    $user = new User;
    $recorder = new UserActivityRecorder;
    $recorder->record($user);

    expect(Cache::has('user_activity_'))->toBeFalse();
});

test('record uses addMinutes(0) for non-integer session lifetime resulting in immediate expiry', function (): void {
    config(['session.lifetime' => 'not-an-integer']);

    $user = new User;
    $user->forceFill(['id' => 99991]);

    $recorder = new UserActivityRecorder;
    $recorder->record($user);

    expect(Cache::has('user_activity_99991'))->toBeFalse();
});
