<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

covers(NotificationDispatchService::class);

uses(RefreshDatabase::class);

test('queue returns null when no mail content found', function (): void {
    Mail::fake();
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $service = app(NotificationDispatchService::class);
    $result = $service->queue($user, $institution->id, 'nonexistent_type', fn ($mc): Mailable => new class extends Mailable
    {
        public function build(): static
        {
            return $this->text('emails.test');
        }
    });

    expect($result)->toBeNull();
    Mail::assertNothingQueued();
});
