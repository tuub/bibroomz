<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\UserHappeningPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UserHappeningPresenter::class);

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $happeningAttributes
 * @return array{institution: Institution, resourceGroup: ResourceGroup, resource: Resource, user: User, happening: Happening}
 */
function makePresenterFixture(array $happeningAttributes = []): array
{
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create(array_merge([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
        'verifier' => null,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => now(),
        'label' => ['en' => 'Test Label'],
    ], $happeningAttributes));

    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    return ['institution' => $institution, 'resourceGroup' => $resourceGroup, 'resource' => $resource, 'user' => $user, 'happening' => $happening];
}

test('present returns array with all top-level keys', function (): void {
    $fixture = makePresenterFixture();

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result)->toHaveKeys([
        'id',
        'user_01',
        'user_02',
        'start',
        'end',
        'can',
        'isVerified',
        'resource',
        'reservedAt',
        'verifiedAt',
        'isVerificationRequired',
        'label',
    ]);
});

test('present resource sub-array contains all expected keys', function (): void {
    $fixture = makePresenterFixture();

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['resource'])->toHaveKeys([
        'id',
        'title',
        'capacity',
        'location',
        'locationUri',
        'description',
        'resourceGroup',
        'institution',
        'institutionId',
    ]);
});

test('present resource id matches the happening resource_id', function (): void {
    $fixture = makePresenterFixture();

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['resource']['id'])->toBe($fixture['happening']->resource_id)
        ->and($result['resource']['institutionId'])->toBe($fixture['institution']->id)
        ->and($result['resource']['institution'])->toBe($fixture['institution']->title);
});

test('present can sub-array contains verify edit delete keys', function (): void {
    $fixture = makePresenterFixture();

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['can'])->toHaveKeys(['verify', 'edit', 'delete']);
});

test('present user_02 returns user2 name when user2 is set', function (): void {
    $user2 = User::factory()->create(['name' => 'second-user']);
    $fixture = makePresenterFixture(['user_id_02' => $user2->id, 'verifier' => 'should.not.appear']);

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['user_02'])->toBe('second-user');
});

test('present user_02 returns verifier string when user2 is null', function (): void {
    $fixture = makePresenterFixture(['user_id_02' => null, 'verifier' => 'expected.verifier']);

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['user_02'])->toBe('expected.verifier');
});

test('present formats start and end as Y-m-d H:i', function (): void {
    $fixture = makePresenterFixture([
        'start' => '2026-06-10 09:00:00',
        'end' => '2026-06-10 10:00:00',
    ]);

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['start'])->toBe('2026-06-10 09:00')
        ->and($result['end'])->toBe('2026-06-10 10:00');
});

test('present id matches happening id', function (): void {
    $fixture = makePresenterFixture();

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['id'])->toBe($fixture['happening']->id);
});

test('present isVerified reflects the happening is_verified flag', function (): void {
    $fixture = makePresenterFixture(['is_verified' => true]);

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($fixture['happening'], $fixture['user']);

    expect($result['isVerified'])->toBeTrue();
});

test('present isVerificationRequired reflects resource setting', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_verification_required' => true]);
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);
    $happening->load(['resource.resource_group.institution', 'user1', 'user2']);

    $presenter = app(UserHappeningPresenter::class);
    $result = $presenter->present($happening, $user);

    expect($result['isVerificationRequired'])->toBeTrue();
});
