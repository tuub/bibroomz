<?php

declare(strict_types=1);

use App\Mail\ClosingMail;
use App\Mail\ClosingMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

covers(ClosingMail::class);

uses(RefreshDatabase::class);

test('constructor assigns closing, happenings, and content from data object', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $envelope = new MailEnvelopeData('from@example.com');
    $happenings = Happening::whereKey([$happening->id])->get();

    $data = new ClosingMailData($closing, $happenings, $content, $envelope);
    $mail = new ClosingMail($data);

    expect($mail->closing->id)->toBe($closing->id)
        ->and($mail->happenings->first()?->id)->toBe($happening->id)
        ->and($mail->content->id)->toBe($content->id)
        ->and($mail->data)->toBe($data);
});

test('implements ShouldQueue and extends Mailable', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $content = MailContent::factory()->create();
    $data = new ClosingMailData($closing, Happening::whereKey([])->get(), $content, new MailEnvelopeData('x@x.com'));

    $mail = new ClosingMail($data);

    expect($mail)->toBeInstanceOf(Mailable::class)
        ->and($mail)->toBeInstanceOf(ShouldQueue::class);
});

test('content returns text and markdown view names', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $content = MailContent::factory()->create();
    $data = new ClosingMailData($closing, Happening::whereKey([])->get(), $content, new MailEnvelopeData('x@x.com'));

    $mail = new ClosingMail($data);
    $mailContent = $mail->content();

    expect($mailContent)->toBeInstanceOf(Content::class)
        ->and($mailContent->text)->toBe('emails.text.mail')
        ->and($mailContent->markdown)->toBe('emails.markdown.mail');
});

test('attachments returns an empty array', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $content = MailContent::factory()->create();
    $data = new ClosingMailData($closing, Happening::whereKey([])->get(), $content, new MailEnvelopeData('x@x.com'));

    $mail = new ClosingMail($data);

    expect($mail->attachments())->toBeArray()->toBe([]);
});
