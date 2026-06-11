<?php

declare(strict_types=1);

use App\Models\BusinessHour;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\WeekDay;
use App\Services\Admin\BusinessHourSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

covers(BusinessHourSynchronizer::class);

uses(RefreshDatabase::class);

test('sync creates new business hours for resource', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '18:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(1);
});

test('sync deletes removed business hours', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $existing = BusinessHour::create([
        'resource_id' => $resource->id,
        'start' => '08:00',
        'end' => '18:00',
    ]);

    $synchronizer = app(BusinessHourSynchronizer::class);
    // sync with empty list — should delete existing
    $synchronizer->sync($resource, []);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(0);
});

test('sync updates existing business hour by id', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $existing = BusinessHour::create([
        'resource_id' => $resource->id,
        'start' => '08:00',
        'end' => '18:00',
    ]);

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => $existing->id,
            'start' => '09:00',
            'end' => '17:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    $updated = BusinessHour::findOrFail($existing->id);
    expect($updated->start)->toBe('09:00')
        ->and($updated->end)->toBe('17:00');
});

test('sync preserves midnight as 00:00 for start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '00:00',
            'end' => '18:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->first();
    expect($bh->start)->toBe('00:00');
});

test('sync converts midnight end to 24:00', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '00:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->first();
    expect($bh->end)->toBe('24:00');
});

test('sync syncs week days for business hour', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    DB::table('week_days')->insert(['day_of_week' => 1, 'key' => 'monday']);
    /** @var WeekDay $weekDay */
    $weekDay = WeekDay::query()->first();

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '18:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [(string) $weekDay->id],
        ],
    ]);

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->with('week_days')->first();
    expect($bh->week_days->count())->toBe(1);
});

test('sync parses start_date and end_date as carbon dates', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '18:00',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'week_days' => [],
        ],
    ]);

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->first();
    expect($bh->start_date)->not->toBeNull()
        ->and($bh->end_date)->not->toBeNull();
});

test('sync updates an existing business hour by id preserving identity', function (): void {
    // RemoveArrayItem would remove the 'id' key from updateOrCreate match array,
    // causing it to always create instead of update.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    // Create a business hour first
    $synchronizer = app(BusinessHourSynchronizer::class);
    $synchronizer->sync($resource, [
        [
            'id' => null,
            'start' => '08:00',
            'end' => '17:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    /** @var BusinessHour $bh */
    $bh = BusinessHour::where('resource_id', $resource->id)->first();
    $existingId = $bh->id;

    // Update using the existing ID - should update, not create a new one
    $synchronizer->sync($resource, [
        [
            'id' => $existingId,
            'start' => '09:00',
            'end' => '18:00',
            'start_date' => null,
            'end_date' => null,
            'week_days' => [],
        ],
    ]);

    // Should still be 1 business hour (updated, not a second one created)
    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(1);
    /** @var BusinessHour $updated */
    $updated = BusinessHour::where('resource_id', $resource->id)->first();
    expect($updated->id)->toBe($existingId)
        ->and($updated->start)->toBe('09:00');
});

test('sync deletes business hours not in list using array_values result', function (): void {
    // UnwrapArrayValues would remove array_values wrapper from businessHourIds.
    // This would cause the array to have non-consecutive numeric keys after filtering,
    // which may affect whereNotIn() behavior.
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    // Create two business hours
    $synchronizer = app(BusinessHourSynchronizer::class);

    $synchronizer->sync($resource, [
        ['id' => null, 'start' => '08:00', 'end' => '17:00', 'start_date' => null, 'end_date' => null, 'week_days' => []],
        ['id' => null, 'start' => '10:00', 'end' => '19:00', 'start_date' => null, 'end_date' => null, 'week_days' => []],
    ]);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(2);

    $ids = BusinessHour::where('resource_id', $resource->id)->pluck('id')->toArray();

    // Sync with only first one - second should be deleted
    $synchronizer->sync($resource, [
        ['id' => $ids[0], 'start' => '08:00', 'end' => '17:00', 'start_date' => null, 'end_date' => null, 'week_days' => []],
    ]);

    expect(BusinessHour::where('resource_id', $resource->id)->count())->toBe(1);
});
