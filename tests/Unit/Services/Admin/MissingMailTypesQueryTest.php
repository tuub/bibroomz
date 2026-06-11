<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\MailType;
use App\Services\Admin\MissingMailTypesQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(MissingMailTypesQuery::class);

uses(RefreshDatabase::class);

test('execute returns all mail types when institution has no mail contents', function (): void {
    $institution = Institution::factory()->create();
    MailType::factory()->create(['key' => 'booking_created']);
    MailType::factory()->create(['key' => 'booking_cancelled']);

    $query = new MissingMailTypesQuery;
    $result = $query->execute($institution->id);

    expect($result->count())->toBeGreaterThanOrEqual(2);
});

test('execute returns collection of MailType', function (): void {
    $institution = Institution::factory()->create();

    $query = new MissingMailTypesQuery;
    $result = $query->execute($institution->id);

    expect($result)->toBeInstanceOf(Collection::class);
});
