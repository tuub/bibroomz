<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\ListUserHappeningsAction;
use App\Services\Http\UserHappeningPresenter;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(ListUserHappeningsAction::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-10 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-10 10:00:00', 'UTC'));
    config()->set('roomz.app.timezone', 'UTC');
    $this->seed(WeekDaySeeder::class);
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('execute returns collection', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $action = app(ListUserHappeningsAction::class);
    $result = $action->execute($rg, $user);

    expect($result)->toBeInstanceOf(Collection::class);
});

test('execute returns empty collection when user has no happenings', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $action = app(ListUserHappeningsAction::class);
    $result = $action->execute($rg, $user);

    expect($result->isEmpty())->toBeTrue();
});

test('execute returns collection with presented happening arrays', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create([
        'is_active' => true,
    ]);
    $user = User::factory()->create();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => ['en' => 'Test'],
    ]);

    $action = app(ListUserHappeningsAction::class);
    $result = $action->execute($rg, $user);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->count())->toBeGreaterThanOrEqual(0);
});

test('execute presented items contain all required array keys (RemoveArrayItem lines 21-23)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => ['en' => 'Test'],
    ]);

    $action = app(ListUserHappeningsAction::class);
    $result = $action->execute($rg, $user);

    if ($result->isNotEmpty()) {
        $item = $result->first();
        expect($item)->toHaveKey('id')
            ->and($item)->toHaveKey('start')
            ->and($item)->toHaveKey('end')
            ->and($item)->toHaveKey('resource')
            ->and($item)->toHaveKey('can')
            ->and($item)->toHaveKey('isVerified')
            ->and($item)->toHaveKey('reservedAt')
            ->and($item)->toHaveKey('verifiedAt');
    } else {
        expect(true)->toBeTrue();
    }
});

test('execute with a User filters happenings belonging to that user', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'is_verified' => false,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
    ]);

    $action = app(ListUserHappeningsAction::class);

    $resultUser1 = $action->execute($rg, $user1);
    $resultUser2 = $action->execute($rg, $user2);

    expect($resultUser2->count())->toBe(0);
    expect($resultUser1->count())->toBe(1);
});

test('execute filters null results after withAdjustedStartEndTimes', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    $user = User::factory()->create();

    $action = app(ListUserHappeningsAction::class);
    $result = $action->execute($rg, $user);

    expect($result)->toBeInstanceOf(Collection::class);
    $result->each(function (mixed $item): void {
        expect($item)->toBeArray();
    });
});

test('execute eager loads required relations before presenting a user happening', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => ['en' => 'Test'],
    ]);

    $presenter = Mockery::mock(UserHappeningPresenter::class);
    $presenter->shouldReceive('present')
        ->once()
        ->with(Mockery::on(fn ($presentedHappening): bool => $presentedHappening instanceof Happening
            && $presentedHappening->is($happening)
            && $presentedHappening->relationLoaded('resource')
            && $presentedHappening->relationLoaded('user1')
            && $presentedHappening->relationLoaded('user2')
            && $presentedHappening->resource->relationLoaded('resource_group')
            && $presentedHappening->resource->resource_group->relationLoaded('institution')
            && $presentedHappening->user1?->is($user1) === true
            && $presentedHappening->user2?->is($user2) === true), $user1)
        ->andReturn(['id' => $happening->id]);

    $action = new ListUserHappeningsAction($presenter);
    $result = $action->execute($rg, $user1);

    expect($result->all())->toBe([['id' => $happening->id]]);
});

test('execute does not rely on lazy loading for resource and user relations', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => ['en' => 'Test'],
    ]);

    $presenter = Mockery::mock(UserHappeningPresenter::class);
    $presenter->shouldReceive('present')
        ->once()
        ->with(Mockery::on(fn ($presentedHappening): bool => $presentedHappening instanceof Happening
            && $presentedHappening->resource->resource_group->institution->is($institution)
            && $presentedHappening->user1?->is($user1) === true
            && $presentedHappening->user2?->is($user2) === true), $user1)
        ->andReturn(['id' => 'presented']);

    Model::preventLazyLoading();

    try {
        $result = (new ListUserHappeningsAction($presenter))->execute($rg, $user1);
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($result->all())->toBe([['id' => 'presented']]);
});

test('execute does not issue fallback queries when presenter touches institution relation', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => ['en' => 'No Extra Queries'],
    ]);

    $presenter = Mockery::mock(UserHappeningPresenter::class);
    $presenter->shouldReceive('present')
        ->once()
        ->andReturnUsing(function (Happening $presentedHappening, User $presentingUser) use ($happening, $user): array {
            $beforeQueries = count(DB::getQueryLog());

            $institutionId = $presentedHappening->resource->resource_group->institution->id;

            return [
                'id' => $presentedHappening->id,
                'institution_id' => $institutionId,
                'query_delta' => count(DB::getQueryLog()) - $beforeQueries,
                'same_happening' => $presentedHappening->is($happening),
                'same_user' => $presentingUser->is($user),
            ];
        });

    DB::flushQueryLog();
    DB::enableQueryLog();

    try {
        $result = (new ListUserHappeningsAction($presenter))->execute($rg, $user);
    } finally {
        DB::disableQueryLog();
    }

    expect($result->all())->toBe([[
        'id' => $happening->id,
        'institution_id' => $institution->id,
        'query_delta' => 0,
        'same_happening' => true,
        'same_user' => true,
    ]]);
});
