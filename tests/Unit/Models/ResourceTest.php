<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Resource::class);

uses(RefreshDatabase::class);

test('resource creates with valid data', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'title' => ['en' => 'Room 101', 'de' => 'Raum 101'],
        'is_active' => true,
        'is_verification_required' => false,
    ]);

    expect($resource->id)->not->toBeNull()
        ->and($resource->resource_group_id)->toBe($resourceGroup->id)
        ->and($resource->is_active)->toBeTrue()
        ->and($resource->is_verification_required)->toBeFalse();
});

test('resource title stores translations', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'title' => ['en' => 'Conference Room', 'de' => 'Konferenzraum'],
    ]);

    expect($resource->getTranslation('title', 'en'))->toBe('Conference Room')
        ->and($resource->getTranslation('title', 'de'))->toBe('Konferenzraum');
});

test('resource resource_group relationship returns BelongsTo', function (): void {
    $resource = new Resource;

    expect($resource->resource_group())->toBeInstanceOf(BelongsTo::class);
});

test('resource resource_group relationship resolves correctly', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    expect($resource->resource_group()->firstOrFail()->id)->toBe($resourceGroup->id);
});

test('resource happenings relationship returns HasMany', function (): void {
    $resource = new Resource;

    expect($resource->happenings())->toBeInstanceOf(HasMany::class);
});

test('resource happenings loads related happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();

    Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    expect($resource->happenings()->count())->toBe(1);
});

test('resource closings relationship returns MorphMany', function (): void {
    $resource = new Resource;

    expect($resource->closings())->toBeInstanceOf(MorphMany::class);
});

test('resource closings loads related closings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    Closing::create([
        'closable_id' => $resource->id,
        'closable_type' => $resource->getMorphClass(),
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Maintenance'],
    ]);

    expect($resource->closings()->count())->toBe(1);
});

test('resource business_hours relationship returns HasMany', function (): void {
    $resource = new Resource;

    expect($resource->business_hours())->toBeInstanceOf(HasMany::class);
});

test('resource is_active and is_verification_required are cast to boolean', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_active' => true,
        'is_verification_required' => true,
    ]);

    expect($resource->is_active)->toBeTrue()
        ->and($resource->is_verification_required)->toBeTrue()
        ->and($resource->isVerificationRequired())->toBeTrue();
});

test('resource isVerificationRequired returns false when not required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);

    expect($resource->isVerificationRequired())->toBeFalse();
});

test('resource scopeActive filters inactive resources', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $active = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => true]);
    $inactive = Resource::factory()->for($resourceGroup, 'resource_group')->create(['is_active' => false]);

    $ids = Resource::query()->active()->pluck('id')->all();

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($inactive->id)
        ->and(count($ids))->toBe(1);
});

test('resource institutionForClosings resolves through resource group', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    expect($resource->institutionForClosings()->is($institution))->toBeTrue();
});
