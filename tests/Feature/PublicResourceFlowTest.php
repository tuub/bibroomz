<?php

covers(
    App\Http\Controllers\ResourceController::class,
    App\Services\Http\ListPublicResourcesAction::class,
    App\Services\Http\GetResourceTimeSlotsAction::class,
    App\Services\Http\PublicResourcePresenter::class,
    App\Services\Resources\GenerateResourceTimeSlotsAction::class,
    App\Services\Happenings\ListCalendarEntriesAction::class,
    App\Services\Happenings\CalendarEntryPresenter::class
);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 08:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function createPublicResourceFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
        'order' => 1,
    ]);

    return compact('institution', 'resourceGroup', 'resource');
}

test('public resources endpoint returns fallback business hours when a resource has no business hours', function () {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
    ] = createPublicResourceFixture();

    $resource->business_hours()->delete();

    $response = $this->getJson(route('resources.get', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'count' => 10,
        'date' => '2026-06-10',
    ]));

    $response->assertOk()
        ->assertJsonPath('resources.0.id', $resource->id)
        ->assertJsonPath('resources.0.resourceGroup', $resourceGroup->id)
        ->assertJsonPath(
            'resources.0.translations.resourceGroup.en',
            $resourceGroup->getTranslations('term_singular')['en'],
        )
        ->assertJsonPath('resources.0.businessHours.0.startTime', '')
        ->assertJsonPath('resources.0.businessHours.0.endTime', '');

    expect($response->json('resources.0.businessHours.0.daysOfWeek'))->toBeArray()->toBeEmpty();
});

test('resource time slots endpoint exposes reserved and closed windows through the public route', function () {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
    ] = createPublicResourceFixture();

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Existing'],
    ]);

    $resource->closings()->create([
        'start' => '2026-06-10 12:00:00',
        'end' => '2026-06-10 13:00:00',
        'description' => ['en' => 'Maintenance'],
    ]);

    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson(route('resource.time_slots', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => $resource->id,
    ]), [
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 11:30:00',
    ]);

    $response->assertOk()->assertJsonStructure(['start', 'end']);

    $startSlots = collect($response->json('start'))->keyBy('label');
    $endSlots = collect($response->json('end'))->keyBy('label');

    expect($startSlots->get('09:00')['is_selected'])->toBeTrue()
        ->and($startSlots->get('10:00')['is_disabled'])->toBeTrue()
        ->and($startSlots->get('12:00')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('09:30')['is_selected'])->toBeTrue()
        ->and($endSlots->get('10:30')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('11:30')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('12:30')['is_disabled'])->toBeTrue();
});

test('calendar entries endpoint returns adjusted happenings and both institution and resource closings', function () {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
    ] = createPublicResourceFixture();

    $institution->closings()->create([
        'start' => '2026-06-10 14:00:00',
        'end' => '2026-06-10 15:00:00',
        'description' => ['en' => 'Institution closed'],
    ]);
    $resourceClosing = $resource->closings()->create([
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => ['en' => 'Resource closed'],
    ]);

    $happening = Happening::create([
        'user_id_01' => User::factory()->create(['name' => 'owner.user'])->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 08:00:00',
        'end' => '2026-06-10 12:00:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Morning booking'],
    ]);

    $response = $this->getJson(route('happenings.get', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'start' => '2026-06-10 00:00:00',
        'end' => '2026-06-10 23:59:59',
    ]));

    $response->assertOk();

    $entries = collect($response->json());
    $happeningEntry = $entries->firstWhere('id', $happening->id);
    $resourceClosingEntry = $entries->firstWhere('id', $resourceClosing->id);
    $institutionClosingEntry = $entries
        ->filter(fn (array $entry): bool => $entry['id'] !== $resourceClosing->id)
        ->firstWhere('classNames', 'closed');

    expect($happeningEntry['start'])->toBe('2026-06-10 09:00')
        ->and($happeningEntry['end'])->toBe('2026-06-10 11:00')
        ->and($happeningEntry['resourceId'])->toBe($resource->id)
        ->and($resourceClosingEntry['display'])->toBe('background')
        ->and($resourceClosingEntry['description']['en'])->toBe('Resource closed')
        ->and($institutionClosingEntry['display'])->toBe('background')
        ->and($institutionClosingEntry['description']['en'])->toBe('Institution closed');
});
