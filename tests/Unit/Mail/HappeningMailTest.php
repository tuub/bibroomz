<?php

declare(strict_types=1);

use App\Mail\HappeningMail;
use App\Mail\HappeningMailData;
use App\Mail\MailEnvelopeData;
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

covers(HappeningMail::class);

uses(RefreshDatabase::class);

test('constructor assigns happening and content from data object', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $envelope = new MailEnvelopeData('from@example.com');

    $data = new HappeningMailData($happening, $content, $envelope);
    $mail = new HappeningMail($data);

    expect($mail->happening->id)->toBe($happening->id)
        ->and($mail->content->id)->toBe($content->id)
        ->and($mail->data)->toBe($data);
});

test('implements ShouldQueue and extends Mailable', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $data = new HappeningMailData($happening, $content, new MailEnvelopeData('x@x.com'));

    $mail = new HappeningMail($data);

    expect($mail)->toBeInstanceOf(Mailable::class)
        ->and($mail)->toBeInstanceOf(ShouldQueue::class);
});

test('content returns text and markdown view names', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $data = new HappeningMailData($happening, $content, new MailEnvelopeData('x@x.com'));

    $mail = new HappeningMail($data);
    $mailContent = $mail->content();

    expect($mailContent)->toBeInstanceOf(Content::class)
        ->and($mailContent->text)->toBe('emails.text.mail')
        ->and($mailContent->markdown)->toBe('emails.markdown.mail');
});

test('attachments returns an empty array', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $data = new HappeningMailData($happening, $content, new MailEnvelopeData('x@x.com'));

    $mail = new HappeningMail($data);

    expect($mail->attachments())->toBeArray()->toBe([]);
});

test('constructor sets locale on the mailable', function (): void {
    // RemoveMethodCall would remove $this->locale(app()->getLocale()).
    // Verify that the mailable's locale is set to the app locale after construction.
    app()->setLocale('de');

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);
    $content = MailContent::factory()->create();
    $data = new HappeningMailData($happening, $content, new MailEnvelopeData('x@x.com'));

    $mail = new HappeningMail($data);

    // Mailable stores locale in $locale property
    expect($mail->locale)->toBe('de');

    app()->setLocale('en');
});
