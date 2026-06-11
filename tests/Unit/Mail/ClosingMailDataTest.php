<?php

declare(strict_types=1);

use App\Mail\ClosingMailData;
use App\Mail\MailEnvelopeData;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ClosingMailData::class);

uses(RefreshDatabase::class);

test('stores all constructor arguments as public properties', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
    ]);
    $happenings = Happening::whereKey([])->get();
    $content = MailContent::factory()->create();
    $envelope = new MailEnvelopeData('from@example.com');

    $data = new ClosingMailData($closing, $happenings, $content, $envelope);

    expect($data->closing)->toBe($closing)
        ->and($data->happenings)->toBe($happenings)
        ->and($data->content)->toBe($content)
        ->and($data->envelope)->toBe($envelope);
});
