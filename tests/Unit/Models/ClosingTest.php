<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(Closing::class);

uses(RefreshDatabase::class);

test('closing creates with valid data for an institution closable', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Test closing', 'de' => 'Testschließung'],
    ]);

    expect($closing->id)->not->toBeNull()
        ->and($closing->closable_id)->toBe($institution->id)
        ->and($closing->closable_type)->toBe(Institution::class)
        ->and($closing->notify_users)->toBeTrue();
});

test('closing start and end fields are cast to datetime', function (): void {
    $institution = Institution::factory()->create();
    $start = CarbonImmutable::parse('2026-07-01 08:00:00');
    $end = CarbonImmutable::parse('2026-07-01 18:00:00');

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => $start,
        'end' => $end,
        'description' => ['en' => 'Datetime cast test'],
    ]);

    expect($closing->start)->not->toBeNull()
        ->and($closing->end)->not->toBeNull()
        ->and($closing->start->format('H:i'))->toBe('08:00')
        ->and($closing->end->format('H:i'))->toBe('18:00');
});

test('closing description field stores translations', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'English desc', 'de' => 'Deutsch Beschreibung'],
    ]);

    expect($closing->getTranslation('description', 'en'))->toBe('English desc')
        ->and($closing->getTranslation('description', 'de'))->toBe('Deutsch Beschreibung');
});

test('closing notify_users field is cast to boolean', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Notify cast'],
        'notify_users' => 0,
    ]);

    expect($closing->notify_users)->toBeFalse()
        ->and($closing->shouldNotifyUsers())->toBeFalse();
});

test('closing soft delete removes it from normal queries', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Soft-delete test'],
    ]);

    $id = $closing->id;
    $closing->delete();

    expect(Closing::find($id))->toBeNull()
        ->and(Closing::withTrashed()->find($id))->not->toBeNull()
        ->and(Closing::withTrashed()->find($id)?->trashed())->toBeTrue();
});

test('closing closable morphTo relationship resolves to institution', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'MorphTo test'],
    ]);

    $closing->load('closable');

    expect($closing->closable)->toBeInstanceOf(Institution::class)
        ->and($closing->closable?->id)->toBe($institution->id);
});

test('closing closable morphTo relationship resolves to resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $closing = Closing::create([
        'closable_id' => $resource->id,
        'closable_type' => $resource->getMorphClass(),
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Resource closing'],
    ]);

    $closing->load('closable');

    expect($closing->closable)->toBeInstanceOf(Resource::class)
        ->and($closing->closable?->id)->toBe($resource->id);
});

test('closing getClosableModel returns correct model instances', function (): void {
    expect(Closing::getClosableModel('institution'))->toBeInstanceOf(Institution::class)
        ->and(Closing::getClosableModel(Institution::class))->toBeInstanceOf(Institution::class)
        ->and(Closing::getClosableModel('resource'))->toBeInstanceOf(Resource::class)
        ->and(Closing::getClosableModel(Resource::class))->toBeInstanceOf(Resource::class);
});

test('closing getClosableModel throws on unsupported type', function (): void {
    expect(fn (): Institution|\App\Models\Resource => Closing::getClosableModel('unknown'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported closable type');
});

test('closing getInstitution resolves through institution closable', function (): void {
    $institution = Institution::factory()->create();

    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now(),
        'end' => now()->addDay(),
        'description' => ['en' => 'Institution path'],
    ]);

    expect($closing->getInstitution()->is($institution))->toBeTrue();
});

test('closing prunable query returns only trashed records', function (): void {
    $closing = new Closing;
    $sql = $closing->prunable()->toSql();

    expect($sql)->toContain('deleted_at');
});
