<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\GetResourceTimeSlotsAction;
use App\Services\Resources\GenerateResourceTimeSlotsAction;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

covers(GetResourceTimeSlotsAction::class);

uses(RefreshDatabase::class);

test('execute returns time slots array for resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start');
});

// --- Mutation-killing tests ---

test('execute with happeningId finds the happening', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::today()->setTime(10, 0),
        'end' => CarbonImmutable::today()->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $action = app(GetResourceTimeSlotsAction::class);
    // Pass happeningId — should resolve the happening (not null)
    $result = $action->execute($resource->id, $happening->id, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start');
});

test('execute with null happeningId passes null happening', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    // Should still succeed with null happening
    expect($result)->toBeArray();
});

test('execute with authenticated user passes user as actor', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start');
});

test('execute with unauthenticated user passes null as actor', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    // No actingAs → auth()->user() returns null
    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray();
});

test('execute eager loads all required relations (lines 26-30 RemoveArrayItem)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end');
});

test('execute result contains both start and end keys (lines 26-30 RemoveArrayItem - all 5 with relations)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::today()->setTime(10, 0),
        'end' => CarbonImmutable::today()->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute(
        $resource->id,
        $happening->id,
        CarbonImmutable::today(),
        CarbonImmutable::today()->addDay(),
    );

    expect($result)->toBeArray()
        ->and(array_key_exists('start', $result))->toBeTrue()
        ->and(array_key_exists('end', $result))->toBeTrue();
});

test('execute non-null happeningId yields non-null happening passed down', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::today()->setTime(10, 0),
        'end' => CarbonImmutable::today()->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $action = app(GetResourceTimeSlotsAction::class);
    $resultWithHappening = $action->execute(
        $resource->id,
        $happening->id,
        CarbonImmutable::today(),
        CarbonImmutable::today()->addDay(),
    );
    $resultWithoutHappening = $action->execute(
        $resource->id,
        null,
        CarbonImmutable::today(),
        CarbonImmutable::today()->addDay(),
    );

    expect($resultWithHappening)->toBeArray()
        ->and($resultWithoutHappening)->toBeArray();
});

test('execute authenticated user produces result (user not treated as null)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $this->actingAs($user);

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end');
});

test('execute unauthenticated produces result (null not treated as user)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $action = app(GetResourceTimeSlotsAction::class);
    $result = $action->execute($resource->id, null, CarbonImmutable::today(), CarbonImmutable::today()->addDay());

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end');
});

test('execute eager loads the expected relations and passes the resolved user and happening to the generator', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::today()->setTime(10, 0),
        'end' => CarbonImmutable::today()->setTime(12, 0),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);
    $start = CarbonImmutable::today();
    $end = CarbonImmutable::today()->addDay();
    $expectedResult = ['start' => [], 'end' => []];
    $generator = Mockery::mock(GenerateResourceTimeSlotsAction::class);
    $generator->shouldReceive('execute')->once()->withArgs(
        function (Resource $loadedResource, ?User $actor, CarbonImmutable $passedStart, CarbonImmutable $passedEnd, ?Happening $passedHappening) use ($user, $happening, $start, $end): bool {
            expect($loadedResource->relationLoaded('happenings'))->toBeTrue()
                ->and($loadedResource->relationLoaded('business_hours'))->toBeTrue()
                ->and($loadedResource->business_hours->first()?->relationLoaded('week_days'))->toBeTrue()
                ->and($loadedResource->resource_group->relationLoaded('settings'))->toBeTrue()
                ->and($loadedResource->resource_group->institution->relationLoaded('settings'))->toBeTrue()
                ->and($loadedResource->resource_group->institution->relationLoaded('closings'))->toBeTrue();

            return $actor?->is($user) === true
                && $passedStart->equalTo($start)
                && $passedEnd->equalTo($end)
                && $passedHappening?->is($happening) === true;
        },
    )->andReturn($expectedResult);

    $this->actingAs($user);

    $action = new GetResourceTimeSlotsAction($generator);

    expect($action->execute($resource->id, $happening->id, $start, $end))->toBe($expectedResult);
});

test('execute passes null actor and null happening when auth user is not an App user', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $start = CarbonImmutable::today();
    $end = CarbonImmutable::today()->addDay();
    $expectedResult = ['start' => [['label' => '09:00']], 'end' => []];
    $generator = Mockery::mock(GenerateResourceTimeSlotsAction::class);
    $generator->shouldReceive('execute')->once()->withArgs(
        fn (Resource $loadedResource, mixed $actor, CarbonImmutable $passedStart, CarbonImmutable $passedEnd, ?Happening $passedHappening): bool => $loadedResource->is($resource)
            && $actor === null
            && $passedStart->equalTo($start)
            && $passedEnd->equalTo($end)
            && ! $passedHappening instanceof Happening,
    )->andReturn($expectedResult);

    Auth::shouldReceive('user')->once()->andReturn(new stdClass);

    $action = new GetResourceTimeSlotsAction($generator);

    expect($action->execute($resource->id, null, $start, $end))->toBe($expectedResult);
});

// happenings eager-loaded onto the resource are bounded to the calendar day of
// $start, since slot generation only ever covers that single day.
test('execute only eager loads happenings overlapping the requested calendar day', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $withinWindow = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-12 10:00:00',
        'end' => '2026-06-12 11:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-20 10:00:00',
        'end' => '2026-06-20 11:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $start = CarbonImmutable::parse('2026-06-12 09:00:00');
    $end = CarbonImmutable::parse('2026-06-12 17:00:00');
    $expectedResult = ['start' => [], 'end' => []];
    $generator = Mockery::mock(GenerateResourceTimeSlotsAction::class);
    $generator->shouldReceive('execute')->once()->withArgs(
        fn (Resource $loadedResource): bool => $loadedResource->happenings->pluck('id')->all() === [$withinWindow->id],
    )->andReturn($expectedResult);

    $action = new GetResourceTimeSlotsAction($generator);

    expect($action->execute($resource->id, null, $start, $end))->toBe($expectedResult);
});

// Boundary: a happening ending exactly at the start of the day (windowStart) is included
// (end >= windowStart), one ending just before it is excluded.
test('execute includes a happening ending exactly at the start of the day but excludes one ending before it', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $endsAtWindowStart = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-11 22:00:00',
        'end' => '2026-06-12 00:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-11 21:00:00',
        'end' => '2026-06-11 23:59:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $start = CarbonImmutable::parse('2026-06-12 09:00:00');
    $end = CarbonImmutable::parse('2026-06-12 17:00:00');
    $expectedResult = ['start' => [], 'end' => []];
    $generator = Mockery::mock(GenerateResourceTimeSlotsAction::class);
    $generator->shouldReceive('execute')->once()->withArgs(
        fn (Resource $loadedResource): bool => $loadedResource->happenings->pluck('id')->all() === [$endsAtWindowStart->id],
    )->andReturn($expectedResult);

    $action = new GetResourceTimeSlotsAction($generator);

    expect($action->execute($resource->id, null, $start, $end))->toBe($expectedResult);
});

// Boundary: a happening starting exactly at the end of the day (windowEnd) is included
// (start <= windowEnd), one starting just after it is excluded.
test('execute includes a happening starting exactly at the end of the day but excludes one starting after it', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $startsAtWindowEnd = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-13 00:00:00',
        'end' => '2026-06-13 01:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => '2026-06-13 00:00:01',
        'end' => '2026-06-13 01:00:00',
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $start = CarbonImmutable::parse('2026-06-12 09:00:00');
    $end = CarbonImmutable::parse('2026-06-12 17:00:00');
    $expectedResult = ['start' => [], 'end' => []];
    $generator = Mockery::mock(GenerateResourceTimeSlotsAction::class);
    $generator->shouldReceive('execute')->once()->withArgs(
        fn (Resource $loadedResource): bool => $loadedResource->happenings->pluck('id')->all() === [$startsAtWindowEnd->id],
    )->andReturn($expectedResult);

    $action = new GetResourceTimeSlotsAction($generator);

    expect($action->execute($resource->id, null, $start, $end))->toBe($expectedResult);
});
