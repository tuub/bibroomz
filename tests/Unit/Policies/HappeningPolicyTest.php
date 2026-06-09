<?php

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Policies\HappeningPolicy;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Concerns\InteractsWithPermissions;

covers(HappeningPolicy::class);

uses(InteractsWithPermissions::class, MockeryPHPUnitIntegration::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seedPermissions();
    config()->set('roomz.app.timezone', 'UTC');
    Carbon::setTestNow(Carbon::parse('2026-06-03 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 10:00:00', 'UTC'));
});

afterEach(function (): void {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

/**
 * @param  array<string, mixed>  $attributes
 */
function createPolicyHappening(array $attributes = []): Happening
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $owner = User::factory()->create(['name' => 'owner-'.Str::uuid()]);
    $second = User::factory()->create(['name' => 'second-'.Str::uuid()]);

    return Happening::create(array_merge([
        'user_id_01' => $owner->id,
        'user_id_02' => $second->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => Utility::normalizeLoginName('verifier-'.Str::uuid()),
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ], $attributes));
}

test('before rejects banned users and create is otherwise allowed', function (): void {
    $user = Mockery::mock(User::class);
    $user->shouldReceive('isBanned')->once()->andReturn(true);

    $policy = new HappeningPolicy;

    expect($policy->before($user))->toBeFalse()
        ->and($policy->create())->toBeTrue();
});

test('update and delete reject past or already verified present happenings', function (): void {
    $user = User::factory()->create(['name' => 'owner']);
    $policy = new HappeningPolicy;

    $past = createPolicyHappening([
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->subHours(3),
        'end' => CarbonImmutable::now()->subHour(),
    ]);
    $verifiedPresent = createPolicyHappening([
        'user_id_01' => $user->id,
        'is_verified' => true,
        'verified_at' => CarbonImmutable::now()->subMinutes(15),
        'start' => CarbonImmutable::now()->subMinutes(30),
        'end' => CarbonImmutable::now()->addMinutes(30),
    ]);

    expect($policy->update($user, $past))->toBeFalse()
        ->and($policy->delete($user, $past))->toBeFalse()
        ->and($policy->update($user, $verifiedPresent))->toBeFalse()
        ->and($policy->delete($user, $verifiedPresent))->toBeFalse();
});

test('update allows owners, second users, and verifiers on active reservations', function (): void {
    $owner = User::factory()->create(['name' => 'owner']);
    $second = User::factory()->create(['name' => 'second']);
    $verifier = User::factory()->create(['name' => 'verifier']);
    $other = User::factory()->create(['name' => 'other']);

    $happening = createPolicyHappening([
        'user_id_01' => $owner->id,
        'user_id_02' => $second->id,
        'verifier' => Utility::normalizeLoginName($verifier->name),
    ]);
    $policy = new HappeningPolicy;

    expect($policy->update($owner, $happening))->toBeTrue()
        ->and($policy->update($second, $happening))->toBeTrue()
        ->and($policy->update($verifier, $happening))->toBeTrue()
        ->and($policy->update($other, $happening))->toBeFalse();
});

test('verify only allows the designated verifier for future unverified happenings', function (): void {
    $verifier = User::factory()->create(['name' => 'verifier']);
    $other = User::factory()->create(['name' => 'other']);
    $policy = new HappeningPolicy;

    $future = createPolicyHappening([
        'verifier' => Utility::normalizeLoginName($verifier->name),
    ]);
    $past = createPolicyHappening([
        'verifier' => Utility::normalizeLoginName($verifier->name),
        'start' => CarbonImmutable::now()->subHours(3),
        'end' => CarbonImmutable::now()->subHour(),
    ]);
    $verified = createPolicyHappening([
        'is_verified' => true,
        'verified_at' => CarbonImmutable::now()->subMinutes(5),
        'verifier' => Utility::normalizeLoginName($verifier->name),
    ]);

    expect($policy->verify($verifier, $future))->toBeTrue()
        ->and($policy->verify($other, $future))->toBeFalse()
        ->and($policy->verify($verifier, $past))->toBeFalse()
        ->and($policy->verify($verifier, $verified))->toBeFalse();
});

test('admin happening actions are scoped to institution permissions', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $happening = Happening::create([
        'user_id_01' => User::factory()->create()->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => 'verifier',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'label' => Utility::getTranslatable('Study'),
    ]);

    $user = User::factory()->create();
    $policy = new HappeningPolicy;

    $this->grantPermission($user, $institution, 'view_happenings');
    $this->grantPermission($user, $institution, 'create_happenings');
    $this->grantPermission($user, $institution, 'edit_happenings');
    $this->grantPermission($user, $institution, 'delete_happenings');

    expect($policy->adminView($user, $happening))->toBeTrue()
        ->and($policy->adminCreate($user, $institution))->toBeTrue()
        ->and($policy->adminUpdate($user, $happening))->toBeTrue()
        ->and($policy->adminDelete($user, $happening))->toBeTrue();
});
