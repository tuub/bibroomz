<?php

declare(strict_types=1);

use App\Http\Controllers\ResourceController;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(ResourceController::class);

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
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource}
 */
function createPublicResourceFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
        'order' => 1,
    ]);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource];
}

// ---------------------------------------------------------------------------
// From PublicResourceFlowTest
// ---------------------------------------------------------------------------

test('public resources endpoint returns fallback business hours when a resource has no business hours', function (): void {
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

test('resource time slots endpoint exposes reserved and closed windows through the public route', function (): void {
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

    /** @var array<int, array{time: string, label: string, is_disabled: bool, is_selected: bool}> $startJson */
    $startJson = $response->json('start') ?? [];
    /** @var array<int, array{time: string, label: string, is_disabled: bool, is_selected: bool}> $endJson */
    $endJson = $response->json('end') ?? [];
    $startSlots = collect($startJson)->keyBy('label');
    $endSlots = collect($endJson)->keyBy('label');

    expect($startSlots->get('09:00')['is_selected'])->toBeTrue()
        ->and($startSlots->get('10:00')['is_disabled'])->toBeTrue()
        ->and($startSlots->get('12:00')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('09:30')['is_selected'])->toBeTrue()
        ->and($endSlots->get('10:30')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('11:30')['is_disabled'])->toBeTrue()
        ->and($endSlots->get('12:30')['is_disabled'])->toBeTrue();
});

test('resource time slots endpoint returns 422 for non-existent resource id', function (): void {
    // ResourceTimeSlotsRequest has exists:resources,id rule so a non-existent UUID triggers 422
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
    ] = createPublicResourceFixture();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('resource.time_slots', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => (string) Str::uuid(),
    ]), [
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 11:00:00',
    ])->assertUnprocessable();
});

test('resource time slots endpoint returns 422 when start and end are missing', function (): void {
    [
        'institution' => $institution,
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
    ] = createPublicResourceFixture();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('resource.time_slots', [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => $resource->id,
    ]), [])->assertUnprocessable();
});

test('calendar entries endpoint returns adjusted happenings and both institution and resource closings', function (): void {
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

    /** @var array<int, array<string, mixed>> $jsonData */
    $jsonData = $response->json() ?? [];
    $entries = collect($jsonData);
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
