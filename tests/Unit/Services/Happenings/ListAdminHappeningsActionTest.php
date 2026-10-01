<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\ListAdminHappeningsAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;

covers(ListAdminHappeningsAction::class);

uses(LazilyRefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('execute returns collection for admin user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $action = app(ListAdminHappeningsAction::class);
    $result = $action->execute($admin);

    expect($result)->toBeInstanceOf(Collection::class);
});

/**
 * The list starts at today, and `happenings.start` is a wall-clock value in the
 * app timezone - so "today" is the app's date. The UTC clock would still be on
 * the previous one for the first hours of the day and list bookings that are
 * already over.
 */
test('execute lists from today in the app timezone', function (): void {
    useAppTimezone('Europe/Berlin');
    // 22:30 UTC on 11 June is already 00:30 on 12 June in Berlin.
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 22:30:00', 'UTC'));

    $admin = User::factory()->create(['is_admin' => true]);
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $yesterday = Happening::factory()->for($resource, 'resource')->create([
        'start' => CarbonImmutable::parse('2026-06-11 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-11 11:00:00'),
    ]);

    $today = Happening::factory()->for($resource, 'resource')->create([
        'start' => CarbonImmutable::parse('2026-06-12 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-12 11:00:00'),
    ]);

    $listed = app(ListAdminHappeningsAction::class)->execute($admin)->pluck('id');

    expect($listed)->toContain($today->id)
        ->and($listed)->not->toContain($yesterday->id);
});
