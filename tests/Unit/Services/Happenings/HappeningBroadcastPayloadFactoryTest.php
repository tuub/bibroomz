<?php

declare(strict_types=1);

use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\HappeningBroadcastPayloadFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

covers(HappeningBroadcastPayloadFactory::class);

uses(MockeryPHPUnitIntegration::class, RefreshDatabase::class);

test('payload contains expected top-level happening key', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload)->toHaveKey('happening');
});

test('payload happening contains required fields', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);
    $h = $payload['happening'];

    expect($h)->toHaveKey('id')
        ->and($h)->toHaveKey('user_01')
        ->and($h)->toHaveKey('user_02')
        ->and($h)->toHaveKey('start')
        ->and($h)->toHaveKey('end')
        ->and($h)->toHaveKey('isVerified')
        ->and($h)->toHaveKey('resource')
        ->and($h)->toHaveKey('reservedAt')
        ->and($h)->toHaveKey('verifiedAt')
        ->and($h)->toHaveKey('can')
        ->and($h)->toHaveKey('isVerificationRequired')
        ->and($h)->toHaveKey('label');
});

test('payload happening id matches the happening', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['id'])->toBe($happening->id)
        ->and($payload['happening']['user_01'])->toBe($user->name);
});

test('payload resource contains institution title', function (): void {
    $institution = Institution::factory()->create(['title' => ['en' => 'Test Institution']]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['resource'])->toHaveKey('institution');
});

test('verifier shown as user_02 when unverified and verifier field set', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'verifier' => 'verifier.person',
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['user_02'])->toBe('verifier.person');
});

test('payload resource contains all required subkeys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);
    $r = $payload['happening']['resource'];

    expect($r)->toHaveKey('id')
        ->and($r)->toHaveKey('title')
        ->and($r)->toHaveKey('capacity')
        ->and($r)->toHaveKey('location')
        ->and($r)->toHaveKey('locationUri')
        ->and($r)->toHaveKey('description')
        ->and($r)->toHaveKey('resourceGroup')
        ->and($r)->toHaveKey('resourceGroupId')
        ->and($r)->toHaveKey('institution');
});

test('payload start and end are formatted as Y-m-d H:i', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    $start = now()->addHour()->setSecond(0)->setMicro(0);
    $end = now()->addHours(2)->setSecond(0)->setMicro(0);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => $start,
        'end' => $end,
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['start'])->toBe($start->format('Y-m-d H:i'))
        ->and($payload['happening']['end'])->toBe($end->format('Y-m-d H:i'));
});

test('user2 name is returned when happening has user_id_02', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create(['name' => 'Second User']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user1);

    expect($payload['happening']['user_02'])->toBe('Second User');
});

test('isVerificationRequired is false when resource does not require verification', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_verification_required' => false]);
    $user = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['isVerificationRequired'])->toBeFalse();
});

test('make falls back to User::findOrFail when user1 relation is null', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create(['name' => 'Found User']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    // Force user1 relation to null so the ?? fallback triggers
    $happening->setRelation('user1', null);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    // CoalesceRemoveLeft would skip User::findOrFail and cause a null->name call
    expect($payload['happening']['user_01'])->toBe('Found User');
});

test('isVerificationRequired is true when resource requires it and user is not admin', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create(['is_admin' => false]);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    // RemoveNot mutation would set isVerificationRequired = resource->is_verification_required && $isAdmin
    // which would be false for non-admin (isAdmin=false), giving wrong result
    expect($payload['happening']['isVerificationRequired'])->toBeTrue();
});

test('isVerificationRequired is false when user is admin even if resource requires it', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ]);

    $factory = new HappeningBroadcastPayloadFactory;
    $payload = $factory->make($happening, $user);

    expect($payload['happening']['isVerificationRequired'])->toBeFalse();
});

test('make eager loads resource, nested institution, user1 and user2 relations', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ])->withoutRelations();

    $factory = new HappeningBroadcastPayloadFactory;
    $factory->make($happening, $user1);

    expect($happening->relationLoaded('resource'))->toBeTrue()
        ->and($happening->relationLoaded('user1'))->toBeTrue()
        ->and($happening->relationLoaded('user2'))->toBeTrue()
        ->and($happening->resource->relationLoaded('resource_group'))->toBeTrue()
        ->and($happening->resource->resource_group->relationLoaded('institution'))->toBeTrue();
});

test('make does not rely on lazy loading for required relations', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ])->withoutRelations();

    Model::preventLazyLoading();

    try {
        $payload = (new HappeningBroadcastPayloadFactory)->make($happening, $user1);
    } finally {
        Model::preventLazyLoading(false);
    }

    expect($payload['happening']['user_01'])->toBe('User One')
        ->and($payload['happening']['user_02'])->toBe('User Two')
        ->and($payload['happening']['resource']['institution'])->toBe($institution->title);
});

test('make calls loadMissing with the full relation set before building the payload', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'user_id_02' => $user2->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ])->load(['resource.resource_group.institution', 'user1', 'user2']);

    $spyHappening = Mockery::mock($happening)->makePartial();
    $spyHappening->shouldReceive('loadMissing')
        ->once()
        ->with(['resource.resource_group.institution', 'user1', 'user2'])
        ->andReturnSelf();

    $payload = (new HappeningBroadcastPayloadFactory)->make($spyHappening, $user1);

    expect($payload['happening']['user_01'])->toBe('User One')
        ->and($payload['happening']['user_02'])->toBe('User Two')
        ->and($payload['happening']['resource']['institution'])->toBe($institution->title);
});

test('make uses the already loaded user1 relation instead of re-querying it', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user1 = User::factory()->create(['name' => 'Loaded User']);
    $recipient = User::factory()->create();

    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user1->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
        'verified_at' => now(),
    ])->load(['resource.resource_group.institution', 'user1']);

    DB::table('users')->where('id', $user1->id)->delete();

    $payload = (new HappeningBroadcastPayloadFactory)->make($happening, $recipient);

    expect($payload['happening']['user_01'])->toBe('Loaded User');
});
