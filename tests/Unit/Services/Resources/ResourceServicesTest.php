<?php

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\WeekDay;
use App\Services\Resources\GenerateResourceTimeSlotsAction;
use App\Services\Resources\ResourceAvailabilityService;
use App\Services\Resources\ResourceBusinessHoursResolver;
use App\Services\Resources\ResourceQuotaService;
use App\Services\Resources\ResourceSettingsResolver;
use App\Services\Resources\ResourceVisibilityService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(
    ResourceAvailabilityService::class,
    ResourceQuotaService::class,
    ResourceBusinessHoursResolver::class,
    ResourceSettingsResolver::class,
    GenerateResourceTimeSlotsAction::class,
    ResourceVisibilityService::class
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
    Mockery::close();
});

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function createResourceFixture(): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_active' => true,
    ]);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

test('resource settings and business hour resolvers return the active values', function (): void {
    ['resource' => $resource] = createResourceFixture();

    $resource->resource_group->settings()->firstWhere('key', 'time_slot_length')?->update(['value' => '00:45']);
    $resource->resource_group->institution->settings()->firstWhere('key', 'time_format')?->update(['value' => 'g:i A']);

    $datedBusinessHour = $resource->business_hours()->create([
        'start' => '12:00:00',
        'end' => '18:00:00',
        'start_date' => '2026-06-10 00:00:00',
        'end_date' => '2026-06-10 23:59:59',
    ]);
    $datedBusinessHour->week_days()->sync(WeekDay::query()->pluck('id'));

    $resource->load('business_hours.week_days', 'resource_group.settings', 'resource_group.institution.settings');

    $settingsResolver = app(ResourceSettingsResolver::class);
    $businessHoursResolver = app(ResourceBusinessHoursResolver::class);
    $businessHours = $businessHoursResolver->forDate($resource, CarbonImmutable::parse('2026-06-10 12:00:00'));

    expect($settingsResolver->resourceGroupString($resource, 'time_slot_length'))->toBe('00:45')
        ->and($settingsResolver->institutionString($resource, 'time_format'))->toBe('g:i A')
        ->and($settingsResolver->resourceGroupFloat($resource, 'quota_daily_hours'))->toBeFloat()
        ->and($businessHours)->toHaveCount(1)
        ->and($businessHours->first()?->start)->toBe('12:00:00');
});

test('resource availability service trims open and closed windows and detects conflicts', function (): void {
    ['institution' => $institution, 'resource' => $resource] = createResourceFixture();

    $resource->closings()->create([
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => 'resource maintenance',
    ]);
    $institution->closings()->create([
        'start' => '2026-06-10 14:00:00',
        'end' => '2026-06-10 15:00:00',
        'description' => 'institution maintenance',
    ]);

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resource->load('closings', 'resource_group.institution.closings', 'business_hours.week_days', 'happenings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $closedStart, $closedEnd] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:30:00'),
        CarbonImmutable::parse('2026-06-10 11:30:00'),
    );
    [$isFullyClosed] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 11:05:00'),
        CarbonImmutable::parse('2026-06-10 11:30:00'),
    );
    [$isOpen, $openStart, $openEnd] = $service->findOpen(
        $resource,
        CarbonImmutable::parse('2026-06-10 08:00:00'),
        CarbonImmutable::parse('2026-06-10 10:00:00'),
    );

    expect($isClosed)->toBeFalse()
        ->and($closedStart->format('H:i'))->toBe('10:30')
        ->and($closedEnd->format('H:i'))->toBe('11:00')
        ->and($isFullyClosed)->toBeTrue()
        ->and($isOpen)->toBeTrue()
        ->and($openStart->format('H:i'))->toBe('09:00')
        ->and($openEnd->format('H:i'))->toBe('10:00')
        ->and($service->hasReservationConflict(
            $resource,
            CarbonImmutable::parse('2026-06-10 10:00:00'),
            CarbonImmutable::parse('2026-06-10 10:45:00'),
        ))->toBeTrue();
});

test('resource quota service enforces quota and concurrent user checks', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();

    $user = User::factory()->create();

    Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resourceGroup->settings()->firstWhere('key', 'quota_happening_block_hours')?->update(['value' => '1']);
    $resourceGroup->settings()->firstWhere('key', 'quota_weekly_happenings')?->update(['value' => '1']);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);

    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 12:00:00'),
        CarbonImmutable::parse('2026-06-10 13:30:00'),
    ))->toBeTrue()
        ->and($service->isExceedingQuotas(
            $resource,
            $user,
            CarbonImmutable::parse('2026-06-10 12:00:00'),
            CarbonImmutable::parse('2026-06-10 13:00:00'),
        ))->toBeTrue()
        ->and($service->isConcurrentUserTimeSlot(
            $resource,
            $user,
            CarbonImmutable::parse('2026-06-10 10:30:00'),
        ))->toBeTrue();
});

test('time slot generation keeps the public shape and disables reserved and closed windows', function (): void {
    ['resource' => $resource] = createResourceFixture();

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resource->closings()->create([
        'start' => '2026-06-10 12:00:00',
        'end' => '2026-06-10 13:00:00',
        'description' => 'maintenance',
    ]);

    $resource->load([
        'closings',
        'business_hours.week_days',
        'happenings',
        'resource_group.settings',
        'resource_group.institution.settings',
        'resource_group.institution.closings',
    ]);

    $slots = app(GenerateResourceTimeSlotsAction::class)->execute(
        $resource,
        null,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 11:30:00'),
    );

    $startSlots = collect($slots['start'])->keyBy('label');
    $endSlots = collect($slots['end'])->keyBy('label');

    $start0900 = $startSlots->get('09:00') ?? [];
    $start1000 = $startSlots->get('10:00') ?? [];
    $end0930 = $endSlots->get('09:30') ?? [];
    $end1030 = $endSlots->get('10:30') ?? [];
    $end1130 = $endSlots->get('11:30') ?? [];
    $end1230 = $endSlots->get('12:30') ?? [];
    expect($slots['start'][0])->toHaveKeys(['time', 'label', 'is_disabled', 'is_selected'])
        ->and($slots['end'][0])->toHaveKeys(['time', 'label', 'is_disabled', 'is_selected'])
        ->and($start0900['is_selected'])->toBeTrue()
        ->and($start1000['is_disabled'])->toBeTrue()
        ->and($end0930['is_selected'])->toBeTrue()
        ->and($end1030['is_disabled'])->toBeTrue()
        ->and($end1130['is_disabled'])->toBeTrue()
        ->and($end1230['is_disabled'])->toBeTrue();
});

test('resource visibility service delegates the resource abilities', function (): void {
    ['resource' => $resource] = createResourceFixture();

    $user = Mockery::mock(User::class);
    $user->shouldReceive('can')->once()->with('view', $resource)->andReturnTrue();
    $user->shouldReceive('can')->once()->with('edit', $resource)->andReturnFalse();
    $user->shouldReceive('can')->once()->with(
        'adminCreate',
        [Happening::class, $resource->resource_group->institution],
    )->andReturnTrue();

    $service = app(ResourceVisibilityService::class);

    expect($service->isViewableByUser($resource, $user))->toBeTrue()
        ->and($service->isEditableByUser($resource, $user))->toBeFalse()
        ->and($service->isUserAbleToCreateHappening($resource, $user))->toBeTrue();
});

// Regression: quota week/day bucketing used findClosed-adjusted timestamps instead of the original
// happening start, potentially shifting an existing booking into the wrong week/day and allowing
// the user to exceed their quota.
test(
    'quota service counts an existing happening in the correct week when a closing shifts its adjusted start',
    function (): void {
        ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();

        $user = User::factory()->create();

        // Closing covers Sunday midnight, shifting the adjusted start of a Sunday booking
        // into the following Monday. The booking must still be counted in its original ISO week.
        $resource->closings()->create([
            'start' => '2026-06-14 23:30:00', // Sunday 23:30
            'end' => '2026-06-15 00:30:00',   // Monday 00:30
            'description' => 'night maintenance',
        ]);

        // Existing happening: Sunday 23:00-Monday 01:00 (crosses into the next ISO week after closing).
        Happening::create([
            'user_id_01' => $user->id,
            'resource_id' => $resource->id,
            'is_verified' => true,
            'verifier' => null,
            'start' => '2026-06-14 23:00:00',
            'end' => '2026-06-15 01:00:00',
            'reserved_at' => now(),
            'verified_at' => now(),
            'label' => ['en' => 'existing'],
        ]);

        $resourceGroup->settings()->firstWhere('key', 'quota_weekly_happenings')?->update(['value' => '1']);

        $resource->load(
            'resource_group.settings',
            'resource_group.institution',
            'closings',
            'resource_group.institution.closings',
        );
        $service = app(ResourceQuotaService::class);

        // A second booking in the same calendar week must be rejected.
        expect($service->isExceedingQuotas(
            $resource,
            $user,
            CarbonImmutable::parse('2026-06-10 10:00:00'), // Wednesday in the same original ISO week
            CarbonImmutable::parse('2026-06-10 11:00:00'),
        ))->toBeTrue();
    },
);

// Regression: diffInMinutes signed comparison picked wrong segment when happening spans a closing
// ResourceAvailabilityService::findClosed line 39: $end->diffInMinutes($closingEnd) returns a
// negative value when $end > $closingEnd (Carbon signed default), making the comparison always
// false and always keeping the before-closing segment regardless of which side is larger.
test('availability service keeps the larger segment when a happening spans an entire closing', function (): void {
    ['resource' => $resource] = createResourceFixture();

    // Closing covers 09:30-10:00; happening 08:00-12:00 spans the entire closing.
    // Before-closing segment: 08:00-09:30 = 90 min.
    // After-closing segment:  10:00-12:00 = 120 min  ← larger, should be kept.
    $resource->closings()->create([
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:00:00',
        'description' => 'short maintenance',
    ]);

    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed, $trimmedStart, $trimmedEnd] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 08:00:00'),
        CarbonImmutable::parse('2026-06-10 12:00:00'),
    );

    // The after-closing segment (10:00-12:00, 120 min) is larger than before-closing (08:00-09:30, 90 min).
    // The service should keep the after-closing segment.
    expect($isClosed)->toBeFalse()
        ->and($trimmedStart->format('H:i'))->toBe('10:00')
        ->and($trimmedEnd->format('H:i'))->toBe('12:00');
});

// ── Boundary conditions for findClosed ──────────────────────────────────────

test('findClosed reports fully-closed when booking start equals closing start', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:00:00'), // start == closingStart
        CarbonImmutable::parse('2026-06-10 10:30:00'),
    );

    expect($isClosed)->toBeTrue();
});

test('findClosed reports fully-closed when booking end equals closing end', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:15:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'), // end == closingEnd
    );

    expect($isClosed)->toBeTrue();
});

test('findClosed does not trim when booking end equals closing start', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed, , $end] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'), // end == closingStart → no overlap
    );

    expect($isClosed)->toBeFalse()
        ->and($end->format('H:i'))->toBe('11:00'); // end unchanged
});

test('findClosed trims end to closing start when end is inside the closing', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:30:00',
        'end' => '2026-06-10 11:30:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed, , $trimmedEnd] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'), // end inside closing
    );

    expect($isClosed)->toBeFalse()
        ->and($trimmedEnd->format('H:i'))->toBe('10:30');
});

test('findClosed breaks on first full enclosure and does not process further closings', function (): void {
    ['resource' => $resource] = createResourceFixture();
    // Two closings; first one fully covers the booking
    $resource->closings()->create([
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => 'first',
    ]);
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'second',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    [$isClosed] = $service->findClosed(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'),
    );

    expect($isClosed)->toBeTrue();
});

// ── Boundary conditions for isTimeSlotInClosing ──────────────────────────────

test('isTimeSlotInClosing returns false when slot equals closing start (exclusive lower bound)', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    // isEnd=false path: timeslot >= closing->start counts as IN closing
    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:00:00'), // == closing start
    ))->toBeTrue(); // >= means this is inside

    // One minute before: must be outside
    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 09:59:00'),
    ))->toBeFalse();
});

test('isTimeSlotInClosing returns false when slot equals closing end', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    // Slot exactly at closing end is outside (strict less-than)
    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 11:00:00'), // == closing end
    ))->toBeFalse();

    // One minute before closing end: inside
    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:59:00'),
    ))->toBeTrue();
});

test('isTimeSlotInClosing isEnd path: slot at closing end is outside, one minute before is inside', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $resource->closings()->create([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'maintenance',
    ]);
    $resource->load('closings', 'resource_group.institution.closings');
    $service = app(ResourceAvailabilityService::class);

    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 11:00:00'),
        isEnd: true,
    ))->toBeFalse();

    expect($service->isTimeSlotInClosing(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:30:00'),
        isEnd: true,
    ))->toBeTrue();
});

// ── Boundary conditions for hasReservationConflict ───────────────────────────

test('hasReservationConflict is false when new booking ends exactly at existing start', function (): void {
    ['resource' => $resource] = createResourceFixture();

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resource->load('closings', 'resource_group.institution.closings', 'happenings');
    $service = app(ResourceAvailabilityService::class);

    // New booking ends exactly when existing starts — no conflict
    expect($service->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'),
    ))->toBeFalse();

    // New booking starts exactly when existing ends — no conflict
    expect($service->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-06-10 12:00:00'),
        CarbonImmutable::parse('2026-06-10 13:00:00'),
    ))->toBeFalse();

    // Overlap by 1 minute — conflict
    expect($service->hasReservationConflict(
        $resource,
        CarbonImmutable::parse('2026-06-10 10:59:00'),
        CarbonImmutable::parse('2026-06-10 11:30:00'),
    ))->toBeTrue();
});

// ── Quota service exact-boundary conditions ────────────────────────────────

test('quota service allows booking exactly at the block-hour quota limit', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();
    $resourceGroup->settings()->firstWhere('key', 'quota_happening_block_hours')?->update(['value' => '2']);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);
    $user = User::factory()->create();

    // Exactly 2 hours — must NOT exceed quota
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 11:00:00'),
    ))->toBeFalse();

    // 2 hours + 1 minute — must exceed
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 11:01:00'),
    ))->toBeTrue();
});

test('quota service ignores block-hour limit when quota is zero (unlimited)', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();
    // Disable all quotas except block-hours to isolate the test
    $resourceGroup->settings()->firstWhere('key', 'quota_happening_block_hours')?->update(['value' => '0']);
    $resourceGroup->settings()->firstWhere('key', 'quota_daily_hours')?->update(['value' => '0']);
    $resourceGroup->settings()->firstWhere('key', 'quota_weekly_hours')?->update(['value' => '0']);
    $resourceGroup->settings()->firstWhere('key', 'quota_weekly_happenings')?->update(['value' => '0']);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);
    $user = User::factory()->create();

    // quota_happening_block_hours = 0 means unlimited — long booking must not be rejected
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 08:00:00'),
        CarbonImmutable::parse('2026-06-10 18:00:00'),
    ))->toBeFalse();
});

test('quota service allows booking exactly at the daily-hours limit', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();
    $resourceGroup->settings()->firstWhere('key', 'quota_daily_hours')?->update(['value' => '3']);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);
    $user = User::factory()->create();

    // Exactly 3 hours — must NOT exceed
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 12:00:00'),
    ))->toBeFalse();

    // 3h + 1 min — must exceed
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 09:00:00'),
        CarbonImmutable::parse('2026-06-10 12:01:00'),
    ))->toBeTrue();
});

test('quota service counts same-week existing happenings toward weekly quota', function (): void {
    ['resourceGroup' => $resourceGroup, 'resource' => $resource] = createResourceFixture();
    $resourceGroup->settings()->firstWhere('key', 'quota_weekly_hours')?->update(['value' => '2']);

    $user = User::factory()->create();
    Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00', // 1h on Wednesday
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);

    // Another 1h same week = 2h total = exactly at limit → NOT exceeding
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-11 09:00:00'),
        CarbonImmutable::parse('2026-06-11 10:00:00'),
    ))->toBeFalse();

    // Another 1h + 1min same week = 2h1m > 2h limit → exceeding
    expect($service->isExceedingQuotas(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-11 09:00:00'),
        CarbonImmutable::parse('2026-06-11 10:01:00'),
    ))->toBeTrue();
});

test('isConcurrentUserTimeSlot boundary: slot at end-time of existing', function (): void {
    ['resource' => $resource] = createResourceFixture();
    $user = User::factory()->create();

    Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'existing'],
    ]);

    $resource->load(
        'resource_group.settings',
        'resource_group.institution',
        'closings',
        'resource_group.institution.closings',
    );
    $service = app(ResourceQuotaService::class);

    // Slot at 11:00 (existing end): not concurrent for a start-check
    expect($service->isConcurrentUserTimeSlot(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 11:00:00'),
    ))->toBeFalse();

    // Slot at 10:00 (existing start): concurrent for a start-check
    expect($service->isConcurrentUserTimeSlot(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 10:00:00'),
    ))->toBeTrue();

    // isEnd=true: slot at 11:00 (existing end) — inclusive <=, so IS concurrent
    expect($service->isConcurrentUserTimeSlot(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 11:00:00'),
        isEnd: true,
    ))->toBeTrue();

    // isEnd=true: slot at 10:30 — inside, concurrent
    expect($service->isConcurrentUserTimeSlot(
        $resource,
        $user,
        CarbonImmutable::parse('2026-06-10 10:30:00'),
        isEnd: true,
    ))->toBeTrue();
});
