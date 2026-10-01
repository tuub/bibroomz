<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Console\RemoveUsersQueryBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

covers(RemoveUsersQueryBuilder::class);

uses(LazilyRefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

test('build returns a query builder', function (): void {
    $builder = new RemoveUsersQueryBuilder;
    $query = $builder->build(30);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('candidates returns collection of non-logged-in users', function (): void {
    $builder = new RemoveUsersQueryBuilder;
    $candidates = $builder->candidates(30);

    expect($candidates)->toBeInstanceOf(Collection::class);
});

test('build excludes admin users', function (): void {
    User::factory()->create(['is_admin' => true]);
    $builder = new RemoveUsersQueryBuilder;
    $query = $builder->build(30);

    $users = $query->get();
    expect($users->where('is_admin', true)->count())->toBe(0);
});

/**
 * The cut-off meets `happenings.end` and the `valid_until` date, both of which
 * are wall-clock values in the app timezone. Read on the UTC clock the window
 * reaches an offset further back than it should, and users whose last activity
 * has in fact dropped out of it keep being protected from removal.
 */
test('build measures the happening window from the app timezone', function (): void {
    useAppTimezone('Europe/Berlin');
    // 22:30 UTC on 11 June is 00:30 on 12 June in Berlin, so a one-day window
    // reaches back to 11 June 00:30 - the UTC clock would reach 10 June 22:30.
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 22:30:00', 'UTC'));

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $stale = User::factory()->create(['is_admin' => false]);
    Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $stale->id,
        'start' => CarbonImmutable::parse('2026-06-10 22:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 23:00:00'),
    ]);

    $recent = User::factory()->create(['is_admin' => false]);
    Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $recent->id,
        'start' => CarbonImmutable::parse('2026-06-11 11:00:00'),
        'end' => CarbonImmutable::parse('2026-06-11 12:00:00'),
    ]);

    $candidates = (new RemoveUsersQueryBuilder)->build(1)->pluck('id');

    expect($candidates)->toContain($stale->id)
        ->and($candidates)->not->toContain($recent->id);
});

test('build measures the membership window from the app timezone', function (): void {
    useAppTimezone('Europe/Berlin');
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-11 22:30:00', 'UTC'));

    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();

    $expired = User::factory()->create(['is_admin' => false]);
    $expired->user_groups()->attach($userGroup->id, [
        'valid_from' => '2026-01-01',
        'valid_until' => '2026-06-11',
    ]);

    $current = User::factory()->create(['is_admin' => false]);
    $current->user_groups()->attach($userGroup->id, [
        'valid_from' => '2026-01-01',
        'valid_until' => '2026-06-13',
    ]);

    $candidates = (new RemoveUsersQueryBuilder)->build(1)->pluck('id');

    expect($candidates)->toContain($expired->id)
        ->and($candidates)->not->toContain($current->id);
});
