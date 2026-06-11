<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Services\Notifications\MailContentLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(MailContentLookup::class);

uses(RefreshDatabase::class);

test('find returns null when no mail content exists', function (): void {
    $institution = Institution::factory()->create();

    $lookup = new MailContentLookup;
    $result = $lookup->find($institution->id, 'booking_created');

    expect($result)->toBeNull();
});

test('find returns mail content when it exists', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::factory()->create(['key' => 'booking_created']);
    MailContent::factory()->for($institution, 'institution')->create(['mail_type_id' => $mailType->id]);

    $lookup = new MailContentLookup;
    $result = $lookup->find($institution->id, 'booking_created');

    expect($result)->toBeInstanceOf(MailContent::class);
});
