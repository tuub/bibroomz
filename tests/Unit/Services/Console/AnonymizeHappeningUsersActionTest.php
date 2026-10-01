<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Console\AnonymizeHappeningUsersAction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

covers(AnonymizeHappeningUsersAction::class);

uses(LazilyRefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('query returns builder for happenings', function (): void {
    $action = new AnonymizeHappeningUsersAction;
    $query = $action->query(30);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('execute anonymizes user references in happenings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'start' => now()->subDays(60),
        'end' => now()->subDays(60)->addHours(2),
    ]);

    $action = new AnonymizeHappeningUsersAction;
    $query = $action->query(30);
    $action->execute($query);

    expect(Happening::find($happening->id)?->user_id_01)->toBeNull();
});

/**
 * `happenings.end` is a wall-clock value in the app timezone. Read on the UTC
 * clock the retention window reaches an offset less far back, and bookings that
 * should already have been anonymized keep their user references.
 */
test('query measures the retention window from the app timezone', function (): void {
    useAppTimezone('Europe/Berlin');
    // 22:30 UTC on 11 June is 00:30 on 12 June in Berlin, so a day back reaches
    // to 11 June 00:30 - the UTC clock would stop at 10 June 22:30.
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 22:30:00', 'UTC'));

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $overdue = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 22:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 23:00:00'),
    ]);

    $action = new AnonymizeHappeningUsersAction;
    $action->execute($action->query(1));

    expect(Happening::find($overdue->id)?->user_id_01)->toBeNull();
});
