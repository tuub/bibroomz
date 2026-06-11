<?php

declare(strict_types=1);

use App\Events\ClosingCreatedEvent;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;
use App\Services\Closings\ClosingNotificationService;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ClosingNotificationService::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('sendForEvent queues no mail when no active mail content exists', function (): void {
    Mail::fake();
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    $service = app(ClosingNotificationService::class);
    $service->sendForEvent($event);

    Mail::assertNothingQueued();
});

test('sendForEvent calls notificationDispatchService queue', function (): void {
    // RemoveMethodCall on line 26 removes the queue() call entirely — no notification would be sent
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    $dispatchService = Mockery::mock(NotificationDispatchService::class);
    $dispatchService->shouldReceive('queue')->once()->andReturn(null);
    app()->instance(NotificationDispatchService::class, $dispatchService);

    $service = app(ClosingNotificationService::class);
    $service->sendForEvent($event);
});

test('sendForEvent passes institution email as from address when email is a string', function (): void {
    // TernaryNegated on line 24 swaps the ternary: when email IS a string it would use '' instead
    $institution = Institution::factory()->create(['email' => 'closing@example.com']);
    $user = User::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    $capturedBuilder = null;
    $dispatchService = Mockery::mock(NotificationDispatchService::class);
    $dispatchService->shouldReceive('queue')
        ->once()
        ->with(
            Mockery::type(User::class),
            Mockery::type('string'),
            Mockery::type('string'),
            Mockery::on(function (Closure $builder) use (&$capturedBuilder): bool {
                $capturedBuilder = $builder;

                return true;
            })
        )
        ->andReturn(null);
    app()->instance(NotificationDispatchService::class, $dispatchService);

    $service = app(ClosingNotificationService::class);
    $service->sendForEvent($event);

    if (! $capturedBuilder instanceof Closure) {
        throw new RuntimeException('Builder closure was not captured');
    }

    $mail = $capturedBuilder(new MailContent);
    expect($mail->data->envelope->fromAddress)->toBe('closing@example.com');
});

test('sendForEvent uses empty string as from address when institution email is null', function (): void {
    // EmptyStringToNotEmpty on line 24 changes fallback '' to a non-empty string
    $institution = Institution::factory()->create(['email' => null]);
    $user = User::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();
    $event = new ClosingCreatedEvent($user, Happening::whereKey([])->get(), $closing);

    $capturedBuilder = null;
    $dispatchService = Mockery::mock(NotificationDispatchService::class);
    $dispatchService->shouldReceive('queue')
        ->once()
        ->with(
            Mockery::type(User::class),
            Mockery::type('string'),
            Mockery::type('string'),
            Mockery::on(function (Closure $builder) use (&$capturedBuilder): bool {
                $capturedBuilder = $builder;

                return true;
            })
        )
        ->andReturn(null);
    app()->instance(NotificationDispatchService::class, $dispatchService);

    $service = app(ClosingNotificationService::class);
    $service->sendForEvent($event);

    if (! $capturedBuilder instanceof Closure) {
        throw new RuntimeException('Builder closure was not captured');
    }

    $mail = $capturedBuilder(new MailContent);
    expect($mail->data->envelope->fromAddress)->toBe('');
});
