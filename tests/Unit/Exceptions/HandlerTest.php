<?php

declare(strict_types=1);

use App\Exceptions\Handler;
use Illuminate\Broadcasting\BroadcastException;

covers(Handler::class);

test('handler can be resolved from container', function (): void {
    $handler = app(Handler::class);

    expect($handler)->toBeInstanceOf(Handler::class);
});

test('handler silently swallows broadcasting exceptions without re-throwing', function (): void {
    $handler = app(Handler::class);
    $exception = new BroadcastException('Broadcast error');
    $handler->report($exception);

    // If we reach this line, the exception was swallowed (not re-thrown)
    expect($handler)->toBeInstanceOf(Handler::class);
});

test('handler renders exceptions as response', function (): void {
    $handler = app(Handler::class);
    $exception = new RuntimeException('Test error');
    $response = $handler->render(request(), $exception);

    expect($response)->not->toBeNull();
});

test('handler renders BroadcastException as 500 websocket JSON response (lines 51 DecrementInteger, IncrementInteger, RemoveArrayItem, RemoveMethodCall)', function (): void {
    $handler = app(Handler::class);
    $exception = new BroadcastException('connection failed');
    $response = $handler->render(request(), $exception);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->toContain('websocket server is unavailable');
});
