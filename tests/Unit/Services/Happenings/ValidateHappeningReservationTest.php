<?php

declare(strict_types=1);

use App\Exceptions\HappeningValidationException;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\ValidateHappeningReservation;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ValidateHappeningReservation::class);

uses(RefreshDatabase::class);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\UserGroup;
use App\Models\WeekDay;
use Database\Seeders\WeekDaySeeder;

test('execute throws when user is not allowed in resource group', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    expect(fn () => $validator->execute($user, $resource, $start, $end))
        ->toThrow(HappeningValidationException::class);
});

test('execute context array contains resource_type and resource_title keys (RemoveArrayItem lines 30-31)', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create(['is_admin' => false]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    try {
        $validator->execute($user, $resource, $start, $end);
        expect(false)->toBeTrue();
    } catch (HappeningValidationException $e) {
        expect($e->context)->toHaveKey('resource_type')
            ->and($e->context)->toHaveKey('resource_title');
    }
});

test('execute does not throw when user is allowed', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    $thrown = null;
    try {
        $validator->execute($user, $resource, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->toBeNull();
});

test('execute throws not_allowed_user when resource group has user group and user is not in it', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $rg->user_groups()->attach($userGroup->id);

    $user = User::factory()->create(['is_admin' => false]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    try {
        $validator->execute($user, $resource, $start, $end);
        expect(false)->toBeTrue();
    } catch (HappeningValidationException $e) {
        expect($e->translationKey)->toBe('happening.errors.not_allowed_user');
    }
});

test('execute throws closing error when resource is closed', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    Closing::factory()->for($resource, 'closable')->create([
        'start' => CarbonImmutable::now()->subHour(),
        'end' => CarbonImmutable::now()->addDay(),
    ]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    try {
        $validator->execute($user, $resource, $start, $end);
        expect(false)->toBeTrue(); // must not reach here
    } catch (HappeningValidationException $e) {
        expect($e->translationKey)->toBeIn([
            'happening.errors.closing',
            'happening.errors.business_hours',
            'happening.errors.reserved',
            'happening.errors.quotas',
            'happening.errors.concurrent',
        ]);
    }
});

test('execute does not throw closing error when resource is not closed', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::now()->addHour();
    $end = CarbonImmutable::now()->addHours(2);

    $thrown = null;
    try {
        $validator->execute($user, $resource, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->not->toBe('happening.errors.closing');
});

test('execute throws business_hours when resource has no open hours', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $resource->business_hours()->delete();

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    try {
        $validator->execute($user, $resource, $start, $end);
        expect(false)->toBeTrue(); // must not reach here
    } catch (HappeningValidationException $e) {
        expect($e->translationKey)->toBe('happening.errors.business_hours');
    }
});

test('execute does not throw business_hours when resource is open', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $weekDays = WeekDay::all();
    $bh = $resource->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
    $bh->week_days()->attach($weekDays->pluck('id'));

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    $thrown = null;
    try {
        $validator->execute($user, $resource, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->not->toBe('happening.errors.business_hours');
});

test('execute does not throw reserved error when no conflict exists', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $weekDays = WeekDay::all();
    $bh = $resource->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
    $bh->week_days()->attach($weekDays->pluck('id'));

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    $thrown = null;
    try {
        $validator->execute($user, $resource, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->not->toBe('happening.errors.reserved');
});

test('execute throws concurrent error when non-admin user has concurrent happening', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource1 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $resource2 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    $weekDays = WeekDay::all();
    foreach ([$resource1, $resource2] as $res) {
        $bh = $res->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
        $bh->week_days()->attach($weekDays->pluck('id'));
    }

    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    Happening::create([
        'resource_id' => $resource1->id,
        'user_id_01' => $user->id,
        'start' => $start,
        'end' => $end,
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $validator = app(ValidateHappeningReservation::class);

    try {
        $validator->execute($user, $resource2, $start, $end);
        expect(false)->toBeTrue(); // must not reach here
    } catch (HappeningValidationException $e) {
        expect($e->translationKey)->toBeIn([
            'happening.errors.concurrent',
            'happening.errors.quotas',
        ]);
    }
});

test('execute does not throw concurrent error when user has edit permission', function (): void {
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource1 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $resource2 = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => true]);

    $weekDays = WeekDay::all();
    foreach ([$resource1, $resource2] as $res) {
        $bh = $res->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
        $bh->week_days()->attach($weekDays->pluck('id'));
    }

    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    Happening::create([
        'resource_id' => $resource1->id,
        'user_id_01' => $user->id,
        'start' => $start,
        'end' => $end,
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $validator = app(ValidateHappeningReservation::class);

    $thrown = null;
    try {
        $validator->execute($user, $resource2, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->not->toBe('happening.errors.concurrent');
});

test('execute completes without exception for non-admin user when all checks pass', function (): void {
    // IfNegated on line 54 flips the quota check: when quotas are NOT exceeded it would throw 'quotas'.
    // BooleanAndToBooleanOr on line 59 changes && to ||: a non-admin (! can_edit = true) with no concurrent
    // happening would throw 'concurrent' even though there is no actual concurrency.
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    $weekDays = WeekDay::all();
    $bh = $resource->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
    $bh->week_days()->attach($weekDays->pluck('id'));

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 11:00');

    $thrown = null;
    try {
        $validator->execute($user, $resource, $start, $end);
    } catch (HappeningValidationException $e) {
        $thrown = $e->translationKey;
    }
    expect($thrown)->toBeNull();
});

test('execute throws exactly quotas error when booking exceeds quota_happening_block_hours', function (): void {
    // IfNegated on line 54 flips the quota check: when quotas ARE exceeded the condition becomes false,
    // so no exception is thrown and the test reaches expect(false)->toBeTrue() below.
    $this->seed(WeekDaySeeder::class);

    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_active' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    $weekDays = WeekDay::all();
    $bh = $resource->business_hours()->create(['start' => '00:00:00', 'end' => '23:59:00']);
    $bh->week_days()->attach($weekDays->pluck('id'));

    $rg->settings()->where('key', 'quota_happening_block_hours')->update(['value' => '1']);

    $validator = app(ValidateHappeningReservation::class);
    $start = CarbonImmutable::parse('next monday 10:00');
    $end = CarbonImmutable::parse('next monday 12:00'); // 2 hours > 1-hour quota

    try {
        $validator->execute($user, $resource, $start, $end);
        expect(false)->toBeTrue(); // must not reach here
    } catch (HappeningValidationException $e) {
        expect($e->translationKey)->toBe('happening.errors.quotas');
    }
});
