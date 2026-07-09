<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\User;
use App\Services\Http\InertiaSharedDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

covers(InertiaSharedDataBuilder::class);

uses(RefreshDatabase::class);

test('build returns route name and null auth when unauthenticated', function (): void {
    $request = Request::create('/');

    $builder = new InertiaSharedDataBuilder;
    $data = $builder->build($request);

    expect($data)->toHaveKey('route')
        ->and($data['auth'])->toBeNull();
});

test('build returns user name when authenticated', function (): void {
    $user = User::factory()->create(['name' => 'Test User']);
    Auth::login($user);

    $request = Request::create('/');

    $builder = new InertiaSharedDataBuilder;
    $data = $builder->build($request);

    expect($data['auth'])->toBeArray()
        ->and($data['auth']['user']['name'])->toBe('Test User');
});

test('build returns the default systemNotification when no app setting exists', function (): void {
    $request = Request::create('/');

    $builder = new InertiaSharedDataBuilder;
    $data = $builder->build($request);

    expect($data['systemNotification'])->toBe('');
});

test('build returns the current app setting system_notification', function (): void {
    AppSetting::set('system_notification', 'Global notice');

    $request = Request::create('/');

    $builder = new InertiaSharedDataBuilder;
    $data = $builder->build($request);

    expect($data['systemNotification'])->toBe('Global notice');
});
