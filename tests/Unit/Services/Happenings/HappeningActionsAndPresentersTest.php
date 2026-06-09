<?php

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\AdminHappeningPresenter;
use App\Services\Happenings\CalendarEntryPresenter;
use App\Services\Happenings\CreateHappeningAction;
use App\Services\Happenings\DeleteHappeningAction;
use App\Services\Happenings\HappeningBroadcaster;
use App\Services\Happenings\HappeningStatusCalculator;
use App\Services\Happenings\ListAdminHappeningsAction;
use App\Services\Happenings\ListCalendarEntriesAction;
use App\Services\Happenings\UpdateHappeningAction;
use App\Services\Happenings\ValidateHappeningReservation;
use App\Services\Happenings\VerifyHappeningAction;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(
    CreateHappeningAction::class,
    UpdateHappeningAction::class,
    DeleteHappeningAction::class,
    VerifyHappeningAction::class,
    CalendarEntryPresenter::class,
    AdminHappeningPresenter::class,
    ListAdminHappeningsAction::class,
    ListCalendarEntriesAction::class
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    Carbon::setTestNow(Carbon::parse('2026-06-03 09:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 09:00:00'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
    Mockery::close();
});

/**
 * @param  array<string, mixed>  $happeningAttributes
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, owner: User, verifier: User, second: User, happening: Happening}
 */
function createHappeningServicesFixture(array $happeningAttributes = []): array
{
    $suffix = uniqid('.', true);
    $institution = Institution::factory()->create(['email' => 'rooms@example.test']);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create([
        'resource_group_id' => $resourceGroup->id,
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'owner'.$suffix]);
    $verifier = User::factory()->create(['name' => 'verifier'.$suffix]);
    $second = User::factory()->create(['name' => 'second'.$suffix]);

    /** @var array<string, mixed> $mergedAttrs */
    $mergedAttrs = array_merge([
        'user_id_01' => $owner->id,
        'user_id_02' => null,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => Utility::normalizeLoginName($verifier->name),
        'start' => CarbonImmutable::parse('2026-06-03 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 11:00:00'),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ], $happeningAttributes);

    $happening = Happening::create($mergedAttrs);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'owner' => $owner, 'verifier' => $verifier, 'second' => $second, 'happening' => $happening];
}

test('create happening action stores normalized reservations for end users', function (): void {
    $fixture = createHappeningServicesFixture();
    $validator = Mockery::mock(ValidateHappeningReservation::class);
    $broadcaster = Mockery::mock(HappeningBroadcaster::class);

    $validator->shouldReceive('execute')->once();
    $broadcaster->shouldReceive('broadcast')
        ->once()
        ->with(Mockery::type(Happening::class), HappeningCreatedEvent::class);

    $action = new CreateHappeningAction($validator, $broadcaster);

    $created = $action->executeForUser(
        $fixture['owner'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 12:00:00'),
        CarbonImmutable::parse('2026-06-03 13:00:00'),
        Utility::getTranslatable('Workshop'),
        'Verifier.User',
    );

    expect($created->user_id_01)->toBe($fixture['owner']->id)
        ->and($created->verifier)->toBe('verifier.user')
        ->and($created->is_verified)->toBeFalse()
        ->and($created->verified_at)->toBeNull()
        ->and($created->getTranslation('label', 'en'))->toBe('Workshop');
});

test('create happening action auto verifies reservations for users with no verifier permission', function (): void {
    $fixture = createHappeningServicesFixture();
    $validator = Mockery::mock(ValidateHappeningReservation::class);
    $broadcaster = Mockery::mock(HappeningBroadcaster::class);

    $this->grantPermission($fixture['owner'], $fixture['institution'], 'no_verifier');

    $validator->shouldReceive('execute')->once();
    $broadcaster->shouldReceive('broadcast')->once();

    $action = new CreateHappeningAction($validator, $broadcaster);

    $created = $action->executeForUser(
        $fixture['owner'],
        $fixture['resource'],
        CarbonImmutable::parse('2026-06-03 12:00:00'),
        CarbonImmutable::parse('2026-06-03 13:00:00'),
        Utility::getTranslatable('Workshop'),
        'Verifier.User',
    );

    expect($created->is_verified)->toBeTrue()
        ->and($created->verified_at)->not->toBeNull()
        ->and($created->verifier)->toBeNull();
});

test('admin create update verify and delete actions reuse shared persistence flow', function (): void {
    $fixture = createHappeningServicesFixture();
    $validator = Mockery::mock(ValidateHappeningReservation::class);
    $broadcaster = Mockery::mock(HappeningBroadcaster::class);

    $validator->shouldReceive('execute')->once();
    $broadcaster->shouldReceive('broadcast')->times(4);

    $createAction = new CreateHappeningAction($validator, $broadcaster);
    $updateAction = new UpdateHappeningAction($validator, $broadcaster);
    $verifyAction = new VerifyHappeningAction($validator, $broadcaster);
    $deleteAction = new DeleteHappeningAction($broadcaster);

    $created = $createAction->executeForAdmin([
        'user_id_01' => $fixture['owner']->id,
        'user_id_02' => null,
        'resource_id' => $fixture['resource']->id,
        'is_verified' => false,
        'verifier' => $fixture['verifier']->name,
        'start' => CarbonImmutable::parse('2026-06-03 14:00:00')->toIsoString(),
        'end' => CarbonImmutable::parse('2026-06-03 15:00:00')->toIsoString(),
        'label' => Utility::getTranslatable('Admin created'),
    ]);

    expect($created->reserved_at)->not->toBeNull();

    $updated = $updateAction->executeForAdmin($created, [
        'start' => CarbonImmutable::parse('2026-06-03 16:00:00')->toIsoString(),
        'end' => CarbonImmutable::parse('2026-06-03 17:00:00')->toIsoString(),
        'label' => Utility::getTranslatable('Admin updated'),
    ]);

    expect($updated->getTranslation('label', 'en'))->toBe('Admin updated');

    $freshHappening = $fixture['happening']->fresh('resource') ?? $fixture['happening'];
    $verified = $verifyAction->execute(
        $fixture['verifier'],
        $freshHappening,
        CarbonImmutable::parse('2026-06-03 10:00:00'),
        CarbonImmutable::parse('2026-06-03 11:00:00'),
    );

    expect($verified->is_verified)->toBeTrue()
        ->and($verified->user_id_02)->toBe($fixture['verifier']->id)
        ->and($verified->verifier)->toBeNull();

    $freshCreated = $created->fresh();
    if ($freshCreated !== null) {
        expect($deleteAction->execute($freshCreated))->toBeTrue();
    }
    expect(Happening::withTrashed()->findOrFail($created->id)->trashed())->toBeTrue();
});

test('user happening update action validates the reservation before broadcasting the update', function (): void {
    $fixture = createHappeningServicesFixture();
    $validator = Mockery::mock(ValidateHappeningReservation::class);
    $broadcaster = Mockery::mock(HappeningBroadcaster::class);
    $start = CarbonImmutable::parse('2026-06-03 12:00:00');
    $end = CarbonImmutable::parse('2026-06-03 13:00:00');

    $validator->shouldReceive('execute')
        ->once()
        ->with($fixture['owner'], Mockery::type(Resource::class), $start, $end, Mockery::type(Happening::class));
    $broadcaster->shouldReceive('broadcast')
        ->once()
        ->with(Mockery::type(Happening::class), HappeningUpdatedEvent::class);

    $action = new UpdateHappeningAction($validator, $broadcaster);
    $updated = $action->executeForUser(
        $fixture['owner'],
        $fixture['happening']->fresh('resource') ?? $fixture['happening'],
        $start,
        $end,
        Utility::getTranslatable('Updated by user'),
    );

    expect($updated->start->format('Y-m-d H:i:s'))->toBe('2026-06-03 12:00:00')
        ->and($updated->end->format('Y-m-d H:i:s'))->toBe('2026-06-03 13:00:00')
        ->and($updated->getTranslation('label', 'en'))->toBe('Updated by user');
});

test('delete happening action returns false when the model cannot be deleted', function (): void {
    $broadcaster = Mockery::mock(HappeningBroadcaster::class);
    $happening = Mockery::mock(Happening::class);

    $broadcaster->shouldReceive('broadcast')->never();
    $happening->shouldReceive('delete')->once()->andReturnFalse();

    expect((new DeleteHappeningAction($broadcaster))->execute($happening))->toBeFalse();
});

test('calendar presenter and list action preserve happening and closing payload shapes', function (): void {
    $fixture = createHappeningServicesFixture();
    $fixture['happening']->update([
        'user_id_02' => $fixture['second']->id,
        'is_verified' => true,
        'verified_at' => CarbonImmutable::parse('2026-06-03 09:30:00'),
    ]);

    $institutionClosing = $fixture['institution']->closings()->create([
        'start' => CarbonImmutable::parse('2026-06-03 14:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 15:00:00'),
        'description' => Utility::getTranslatable('Institution maintenance'),
    ]);

    $resourceClosing = $fixture['resource']->closings()->create([
        'start' => CarbonImmutable::parse('2026-06-03 16:00:00'),
        'end' => CarbonImmutable::parse('2026-06-03 17:00:00'),
        'description' => Utility::getTranslatable('Room maintenance'),
    ]);

    $presenter = new CalendarEntryPresenter(new HappeningStatusCalculator);
    $happeningEntry = $presenter->presentHappening(
        $fixture['happening']->fresh(['resource.resource_group.institution', 'user1', 'user2']) ?? $fixture['happening'],
        $fixture['owner'],
    );

    $action = new ListCalendarEntriesAction($presenter);
    $closingEntries = $action->execute(
        $fixture['resourceGroup']->fresh(['institution.closings', 'resources.closings']) ?? $fixture['resourceGroup'],
        CarbonImmutable::parse('2026-06-03 00:00:00'),
        CarbonImmutable::parse('2026-06-03 23:59:59'),
        $fixture['owner'],
    )->where('classNames', 'closed')->values();

    $closing0 = $closingEntries[0] ?? [];
    $closing1 = $closingEntries[1] ?? [];
    expect($happeningEntry['status']['type'])->toBe('user-booking')
        ->and($happeningEntry['resource']['institution'])->toBe($fixture['institution']->title)
        ->and($closingEntries)->toHaveCount(2)
        ->and($closing0['id'])->toBe($institutionClosing->id)
        ->and($closing0['display'])->toBe('background')
        ->and($closing1['id'])->toBe($resourceClosing->id)
        ->and($closing1['classNames'])->toBe('closed');
});

test('admin happening list action only returns viewable upcoming happenings', function (): void {
    $allowed = createHappeningServicesFixture();
    $denied = createHappeningServicesFixture([
        'start' => CarbonImmutable::parse('2026-06-04 10:00:00'),
        'end' => CarbonImmutable::parse('2026-06-04 11:00:00'),
    ]);
    $viewer = User::factory()->create(['name' => 'viewer.user']);

    $this->grantPermission($viewer, $allowed['institution'], 'view_happenings');

    $action = new ListAdminHappeningsAction(new AdminHappeningPresenter);
    $items = $action->execute($viewer);

    $firstItem = $items->first() ?? [];
    expect($items)->toHaveCount(1)
        ->and($firstItem['id'])->toBe($allowed['happening']->id)
        ->and($firstItem['institution_id'])->toBe($allowed['institution']->id)
        ->and($firstItem['resource'])->toBe($allowed['resource']->getTranslations('title'));
});
