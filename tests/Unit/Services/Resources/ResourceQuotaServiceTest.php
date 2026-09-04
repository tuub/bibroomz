<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Resources\ResourceQuotaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceQuotaService::class);

uses(RefreshDatabase::class);

test('isExceedingQuotas returns false when user is null', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $service = app(ResourceQuotaService::class);
    $result = $service->isExceedingQuotas($resource, null, CarbonImmutable::now()->addHour(), CarbonImmutable::now()->addHours(2));

    expect($result)->toBeFalse();
});

test('isExceedingQuotas returns false for admin user with unlimited_quotas', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $service = app(ResourceQuotaService::class);
    $result = $service->isExceedingQuotas($resource, $admin, CarbonImmutable::now()->addHour(), CarbonImmutable::now()->addHours(2));

    expect($result)->toBeFalse();
});

test('isExceedingQuotas returns true for a regular user when the happening block quota is exceeded', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1']);

    $service = app(ResourceQuotaService::class);
    $result = $service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 11:30:00'),
    );

    expect($result)->toBeTrue();
});

test('isExceedingQuotas still returns false for admin users even when quotas would otherwise be exceeded', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '1']);

    $service = app(ResourceQuotaService::class);
    $result = $service->isExceedingQuotas(
        $resource,
        $admin,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 12:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas does not exceed the weekly happening quota with only the new happening', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas exceeds the weekly happening quota when another same-week happening exists', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeTrue();
});

test('isExceedingQuotas treats a zero happening block quota as unlimited', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 13:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas does not exceed the happening block quota when duration equals the quota', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '2']);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 11:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas uses exact hour conversion below a fractional block quota threshold', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '2.01']);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 11:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas still exceeds a fractional block quota just below the actual duration', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1.99']);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 11:00:00'),
    );

    expect($result)->toBeTrue();
});

test('isExceedingQuotas does not exceed the weekly happening quota when the total exactly matches the limit', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '2']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas exceeds the weekly hours quota when total hours are above a one-hour limit', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeTrue();
});

test('isExceedingQuotas does not exceed the weekly hours quota when total hours exactly match the limit', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '2']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeFalse();
});

test('isExceedingQuotas exceeds the daily hours quota when another same-day happening pushes total above one hour', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '1']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-12 07:00:00'),
        'end' => CarbonImmutable::parse('2026-06-12 08:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeTrue();
});

// Laravel can cache the resolved controller (and its injected ResourceQuotaService)
// on the Route object across multiple requests in one test. otherHappenings() memoizes per
// user/resource-group/week, so without the Happening::saved listener resetting that cache, a
// happening created after the first call would never be seen by a later call on the same
// service instance.
test('isExceedingQuotas cache is invalidated when a new happening is saved on the same service instance', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $service = app(ResourceQuotaService::class);

    $start = CarbonImmutable::parse('2026-06-12 09:00:00');
    $end = CarbonImmutable::parse('2026-06-12 10:00:00');

    // First call caches an empty "other happenings" result for this user/resource-group/week.
    expect($service->isExceedingQuotas($resource, $user, $start, $end))->toBeFalse();

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    // Without cache invalidation on save, this would still see the stale empty result.
    expect($service->isExceedingQuotas($resource, $user, $start, $end))->toBeTrue();
});

test('isExceedingQuotas cache is invalidated when a happening is deleted on the same service instance', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '1']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '0']);

    $otherHappening = $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-10 09:00:00'),
        'end' => CarbonImmutable::parse('2026-06-10 10:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $service = app(ResourceQuotaService::class);

    $start = CarbonImmutable::parse('2026-06-12 09:00:00');
    $end = CarbonImmutable::parse('2026-06-12 10:00:00');

    // First call caches the result including $otherHappening.
    expect($service->isExceedingQuotas($resource, $user, $start, $end))->toBeTrue();

    $otherHappening->delete();

    // Without cache invalidation on delete, this would still count the deleted happening.
    expect($service->isExceedingQuotas($resource, $user, $start, $end))->toBeFalse();
});

test('isExceedingQuotas does not exceed the daily hours quota when the total exactly matches the limit', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_happenings')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_weekly_hours')->update(['value' => '0']);
    $rg->settings()->where('key', 'quota_daily_hours')->update(['value' => '2']);

    $resource->happenings()->create([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::parse('2026-06-12 07:00:00'),
        'end' => CarbonImmutable::parse('2026-06-12 08:00:00'),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $result = app(ResourceQuotaService::class)->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-12 09:00:00'),
        CarbonImmutable::parse('2026-06-12 10:00:00'),
    );

    expect($result)->toBeFalse();
});
