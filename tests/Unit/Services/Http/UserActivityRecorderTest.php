<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Http\UserActivityRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

covers(UserActivityRecorder::class);

uses(RefreshDatabase::class);

test('record stores user activity in cache', function (): void {
    $user = User::factory()->create();
    $recorder = app(UserActivityRecorder::class);
    $recorder->record($user);

    $key = 'user_activity_'.$user->id;
    expect(Cache::has($key))->toBeTrue();
});

test('record does not throw when called multiple times for same user', function (): void {
    $user = User::factory()->create();
    $recorder = app(UserActivityRecorder::class);

    $recorder->record($user);
    $recorder->record($user);

    $key = 'user_activity_'.$user->id;
    expect(Cache::has($key))->toBeTrue();
});
