<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Services\AdminLoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

covers(AdminLoggingService::class);

uses(RefreshDatabase::class);

test('log writes to admin channel', function (): void {
    Log::shouldReceive('channel')->with('admin')->once()->andReturnSelf();
    Log::shouldReceive('info')->once()->with(Mockery::type('string'));

    $institution = Institution::factory()->create();
    $service = new AdminLoggingService;
    $service->log('created', $institution);
});

test('log formats message with model class and action', function (): void {
    $captured = null;
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->andReturnUsing(function (string $msg) use (&$captured): void {
        $captured = $msg;
    });

    $institution = Institution::factory()->create();
    $service = new AdminLoggingService;
    $service->log('updated', $institution);

    expect($captured)->toContain('updated')
        ->and($captured)->toContain('Institution');
});

test('log includes string cast of model id in message', function (): void {
    // RemoveStringCast would remove (string) cast from $modelId.
    // The message must contain the model id as a string.
    $captured = null;
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->andReturnUsing(function (string $msg) use (&$captured): void {
        $captured = $msg;
    });

    $institution = Institution::factory()->create();
    $service = new AdminLoggingService;
    $service->log('deleted', $institution);

    expect($captured)->toContain($institution->id);
});

test('log includes string cast of user id when authenticated', function (): void {
    // RemoveStringCast would remove (string) cast from $userId.
    $user = User::factory()->create();
    $this->actingAs($user);

    $captured = null;
    Log::shouldReceive('channel')->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->andReturnUsing(function (string $msg) use (&$captured): void {
        $captured = $msg;
    });

    $institution = Institution::factory()->create();
    $service = new AdminLoggingService;
    $service->log('created', $institution);

    // The user id should appear in the log message
    expect($captured)->toContain((string) $user->id);
});
