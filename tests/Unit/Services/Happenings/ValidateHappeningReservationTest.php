<?php

use App\Exceptions\HappeningValidationException;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\CreateHappeningAction;
use App\Services\Happenings\ValidateHappeningReservation;
use App\Services\Resources\ResourceAvailabilityService;
use App\Services\Resources\ResourceQuotaService;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

covers(ValidateHappeningReservation::class);

uses(RefreshDatabase::class);

afterEach(fn () => Mockery::close());

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup&MockInterface, resource: Resource&MockInterface, user: User&MockInterface, happening: Happening&MockInterface}
 */
function buildValidationFixture(): array
{
    $institution = new Institution(['title' => 'Library']);
    /** @var ResourceGroup&MockInterface $resourceGroup */
    $resourceGroup = Mockery::mock(ResourceGroup::class)->makePartial();
    $resourceGroup->term_singular = 'Room';
    $resourceGroup->setRelation('institution', $institution);

    /** @var Resource&MockInterface $resource */
    $resource = Mockery::mock(Resource::class)->makePartial();
    $resource->title = 'Quiet Room';
    $resource->setRelation('resource_group', $resourceGroup);

    /** @var User&MockInterface $user */
    $user = Mockery::mock(User::class)->makePartial();
    /** @var Happening&MockInterface $happening */
    $happening = Mockery::mock(Happening::class)->makePartial();

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'user' => $user, 'happening' => $happening];
}

test('reservation validation rejects users outside the allowed groups', function (): void {
    $fixture = buildValidationFixture();
    $service = new ValidateHappeningReservation(
        Mockery::mock(ResourceAvailabilityService::class),
        Mockery::mock(ResourceQuotaService::class),
    );

    $fixture['resourceGroup']->shouldReceive('isAllowedUser')->once()->with($fixture['user'])->andReturnFalse();

    expect(fn () => $service->execute(
        $fixture['user'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 10:00:00'),
        CarbonImmutable::parse('2026-06-03 11:00:00'),
    ))->toThrow(HappeningValidationException::class, __('happening.errors.not_allowed_user', [
        'resource_type' => 'Room',
        'resource_title' => 'Quiet Room',
    ]));
});

test('reservation validation rejects conflicting bookings', function (): void {
    $fixture = buildValidationFixture();
    $availabilityService = Mockery::mock(ResourceAvailabilityService::class);
    $quotaService = Mockery::mock(ResourceQuotaService::class);
    $service = new ValidateHappeningReservation($availabilityService, $quotaService);

    $fixture['resourceGroup']->shouldReceive('isAllowedUser')->once()->andReturnTrue();
    $availabilityService->shouldReceive('findClosed')->once()->andReturn([false]);
    $availabilityService->shouldReceive('findOpen')->once()->andReturn([true]);
    $availabilityService->shouldReceive('hasReservationConflict')->once()->andReturnTrue();

    expect(fn () => $service->execute(
        $fixture['user'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 10:00:00'),
        CarbonImmutable::parse('2026-06-03 11:00:00'),
        $fixture['happening'],
    ))->toThrow(HappeningValidationException::class, __('happening.errors.reserved', [
        'resource_type' => 'Room',
        'resource_title' => 'Quiet Room',
    ]));
});

test('reservation validation rejects concurrent user bookings for non editors', function (): void {
    $fixture = buildValidationFixture();
    $availabilityService = Mockery::mock(ResourceAvailabilityService::class);
    $quotaService = Mockery::mock(ResourceQuotaService::class);
    $service = new ValidateHappeningReservation($availabilityService, $quotaService);

    $fixture['resourceGroup']->shouldReceive('isAllowedUser')->once()->andReturnTrue();
    $availabilityService->shouldReceive('findClosed')->once()->andReturn([false]);
    $availabilityService->shouldReceive('findOpen')->once()->andReturn([true]);
    $availabilityService->shouldReceive('hasReservationConflict')->once()->andReturnFalse();
    $quotaService->shouldReceive('isExceedingQuotas')->once()->andReturnFalse();
    $fixture['user']->shouldReceive('can')->once()->with('edit', $fixture['institution'])->andReturnFalse();
    $fixture['user']->shouldReceive('isHavingConcurrentHappening')->once()->andReturnTrue();

    expect(fn () => $service->execute(
        $fixture['user'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 10:00:00'),
        CarbonImmutable::parse('2026-06-03 11:00:00'),
        $fixture['happening'],
    ))->toThrow(HappeningValidationException::class, __('happening.errors.concurrent'));
});

test('reservation validation passes when every availability check succeeds', function (): void {
    $fixture = buildValidationFixture();
    $availabilityService = Mockery::mock(ResourceAvailabilityService::class);
    $quotaService = Mockery::mock(ResourceQuotaService::class);
    $service = new ValidateHappeningReservation($availabilityService, $quotaService);

    $fixture['resourceGroup']->shouldReceive('isAllowedUser')->once()->andReturnTrue();
    $availabilityService->shouldReceive('findClosed')->once()->andReturn([false]);
    $availabilityService->shouldReceive('findOpen')->once()->andReturn([true]);
    $availabilityService->shouldReceive('hasReservationConflict')->once()->andReturnFalse();
    $quotaService->shouldReceive('isExceedingQuotas')->once()->andReturnFalse();
    $fixture['user']->shouldReceive('can')->once()->with('edit', $fixture['institution'])->andReturnTrue();

    $service->execute(
        $fixture['user'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 10:00:00'),
        CarbonImmutable::parse('2026-06-03 11:00:00'),
        $fixture['happening'],
    );
    expect(true)->toBeTrue();
});

// Document the current admin override contract explicitly: executeForAdmin bypasses domain
// validation — double-booking, closing checks, quota checks, and business-hours checks are
// skipped. This is intentional, and the test keeps that behavior visible if the action changes.
test('admin happening creation bypasses domain validation and allows overlap with closings', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => true]);
    $owner = User::factory()->create();

    // Add a closing covering the entire booking window.
    $resource->closings()->create([
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 11:00:00',
        'description' => 'Full maintenance window',
    ]);

    Event::fake();
    $action = app(CreateHappeningAction::class);

    // executeForAdmin must succeed even though the slot is fully closed.
    $happening = $action->executeForAdmin([
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Admin override'],
    ]);

    expect($happening->resource_id)->toBe($resource->id);
});
