<?php

covers(
    App\Http\Controllers\HappeningController::class,
    App\Services\Happenings\CreateHappeningAction::class,
    App\Services\Happenings\UpdateHappeningAction::class,
    App\Services\Happenings\DeleteHappeningAction::class,
    App\Services\Happenings\VerifyHappeningAction::class,
    App\Services\Happenings\HappeningNotificationService::class,
    App\Listeners\HappeningEventSubscriber::class
);

use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningsChangedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

function createPublicHappeningFixture(): array
{
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
    ]);
    $owner = User::factory()->create(['name' => 'owner.user']);
    $verifier = User::factory()->create(['name' => 'verifier.user']);
    $otherUser = User::factory()->create(['name' => 'other.user']);

    return compact('institution', 'resourceGroup', 'resource', 'owner', 'verifier', 'otherUser');
}

test('guest requests are rejected for sanctum protected public happening routes', function () {
    ['resourceGroup' => $resourceGroup] = createPublicHappeningFixture();

    $this->getJson(route('user.happenings.get', ['resource_group_id' => $resourceGroup->id]))
        ->assertUnauthorized();

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => (string) fake()->uuid()],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ])->assertUnauthorized();
});

test('my happenings route returns adjusted open entries and filters out fully closed ones', function () {
    [
        'resourceGroup' => $resourceGroup,
        'resource' => $resource,
        'owner' => $owner,
        'verifier' => $verifier,
    ] = createPublicHappeningFixture();

    $resource->closings()->create([
        'start' => '2026-06-10 11:00:00',
        'end' => '2026-06-10 12:00:00',
        'description' => ['en' => 'Midday close'],
    ]);

    $visible = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 08:00:00',
        'end' => '2026-06-10 12:00:00',
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => ['en' => 'Visible'],
    ]);

    $filtered = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 11:05:00',
        'end' => '2026-06-10 11:30:00',
        'reserved_at' => now()->subHour(),
        'verified_at' => null,
        'label' => ['en' => 'Filtered'],
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson(route('user.happenings.get', ['resource_group_id' => $resourceGroup->id]));

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $visible->id)
        ->assertJsonPath('0.start', '2026-06-10 09:00')
        ->assertJsonPath('0.end', '2026-06-10 11:00')
        ->assertJsonPath('0.user_02', $verifier->name);

    expect(collect($response->json())->pluck('id')->all())->not->toContain($filtered->id);
});

test('authenticated users can create a public reservation and dispatch events', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    Event::fake([HappeningCreatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Study slot'],
    ])->assertNoContent();

    $happening = Happening::firstWhere('resource_id', $resource->id);

    expect($happening)->not->toBeNull()
        ->and($happening?->user_id_01)->toBe($owner->id)
        ->and($happening?->is_verified)->toBeFalse()
        ->and($happening?->verifier)->toBe($verifier->name);

    Event::assertDispatched(HappeningCreatedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('overlapping reservations are rejected with the translated public error message', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => null,
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Existing'],
    ]);

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'verifier' => $verifier->name,
        'label' => ['en' => 'Conflicting'],
    ])->assertStatus(400)
        ->assertJsonPath('message', __('happening.errors.reserved', [
            'resource_type' => $resource->resource_group->term_singular,
            'resource_title' => $resource->title,
        ]));
});

test('verifier required reservations reject missing verifier input', function () {
    ['resource' => $resource, 'owner' => $owner] = createPublicHappeningFixture();

    Sanctum::actingAs($owner);

    $this->postJson(route('happening.add'), [
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['verifier']);
});

test('owners can update reservations and dispatch update events', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Original'],
    ]);

    Event::fake([HappeningUpdatedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->postJson(route('happening.update', ['id' => $happening->id]), [
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'label' => ['en' => 'Updated'],
    ])->assertNoContent();

    $happening->refresh();

    expect($happening->start->format('Y-m-d H:i:s'))->toBe('2026-06-10 10:00:00')
        ->and($happening->end->format('Y-m-d H:i:s'))->toBe('2026-06-10 11:00:00')
        ->and($happening->getTranslations('label')['en'])->toBe('Updated');

    Event::assertDispatched(HappeningUpdatedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('designated verifiers can verify reservations and dispatch verification events', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Needs verification'],
    ]);

    Event::fake([HappeningVerifiedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($verifier);

    $this->postJson(route('happening.verify', ['id' => $happening->id]), [
        'start' => '2026-06-10 09:30:00',
        'end' => '2026-06-10 10:30:00',
    ])->assertNoContent();

    $happening->refresh();

    expect($happening->is_verified)->toBeTrue()
        ->and($happening->user_id_02)->toBe($verifier->id)
        ->and($happening->verifier)->toBeNull()
        ->and($happening->verified_at)->not->toBeNull()
        ->and($happening->start->format('Y-m-d H:i:s'))->toBe('2026-06-10 09:30:00')
        ->and($happening->end->format('Y-m-d H:i:s'))->toBe('2026-06-10 10:30:00');

    Event::assertDispatched(HappeningVerifiedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});

test('authorized users can delete reservations and dispatch deletion events', function () {
    ['resource' => $resource, 'owner' => $owner, 'verifier' => $verifier] = createPublicHappeningFixture();

    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
        'reserved_at' => now(),
        'verified_at' => null,
        'label' => ['en' => 'Delete me'],
    ]);

    Event::fake([HappeningDeletedEvent::class, HappeningsChangedEvent::class]);
    Sanctum::actingAs($owner);

    $this->deleteJson(route('happening.delete', ['id' => $happening->id]))
        ->assertOk();

    expect(Happening::withTrashed()->find($happening->id)?->trashed())->toBeTrue();

    Event::assertDispatched(HappeningDeletedEvent::class);
    Event::assertDispatched(HappeningsChangedEvent::class);
});
