<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Services\Closings\ListClosingsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ListClosingsAction::class);

uses(RefreshDatabase::class);

test('execute returns closings for institution', function (): void {
    $institution = Institution::factory()->create();
    Closing::factory()->for($institution, 'closable')->count(2)->create();

    $action = app(ListClosingsAction::class);
    $result = $action->execute($institution);

    expect($result)->toHaveCount(2);
});

test('execute returns empty collection when no closings', function (): void {
    $institution = Institution::factory()->create();

    $action = app(ListClosingsAction::class);
    $result = $action->execute($institution);

    expect($result)->toBeEmpty();
});
