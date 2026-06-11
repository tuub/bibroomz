<?php

declare(strict_types=1);

use App\Http\Requests\CalendarEntriesRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\RouteResourceGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(CalendarEntriesRequest::class);

uses(RefreshDatabase::class);

test('CalendarEntriesRequest defines validation rules', function (): void {
    $request = new CalendarEntriesRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('start')
        ->and($rules['start'])->toContain('required')
        ->and($rules['start'])->toContain('date')
        ->and($rules)->toHaveKey('end')
        ->and($rules['end'])->toContain('required')
        ->and($rules['end'])->toContain('date');
});

test('CalendarEntriesRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new CalendarEntriesRequest;

    expect($request->authorize())->toBeTrue();
});

test('authorize returns true for any authenticated user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(CalendarEntriesRequest::class, [], $user);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns true even without authentication', function (): void {
    $request = buildFormRequest(CalendarEntriesRequest::class, []);

    expect($request->authorize())->toBeTrue();
});

test('rules contains institution_slug key', function (): void {
    $rules = (new CalendarEntriesRequest)->rules();

    expect($rules)->toHaveKey('institution_slug')
        ->and($rules['institution_slug'])->toContain('required')
        ->and($rules['institution_slug'])->toContain('string');
});

test('rules contains resource_group_slug key', function (): void {
    $rules = (new CalendarEntriesRequest)->rules();

    expect($rules)->toHaveKey('resource_group_slug')
        ->and($rules['resource_group_slug'])->toContain('required')
        ->and($rules['resource_group_slug'])->toContain('string');
});

test('rules contains start key with required and date', function (): void {
    $rules = (new CalendarEntriesRequest)->rules();

    expect($rules)->toHaveKey('start')
        ->and($rules['start'])->toContain('required')
        ->and($rules['start'])->toContain('date');
});

test('rules contains end key with required and date', function (): void {
    $rules = (new CalendarEntriesRequest)->rules();

    expect($rules)->toHaveKey('end')
        ->and($rules['end'])->toContain('required')
        ->and($rules['end'])->toContain('date');
});

test('resourceGroup resolves once with the exact relation list and reuses the cached model', function (): void {
    $institution = Institution::factory()->create(['slug' => 'main-campus']);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'seminar-rooms']);
    $resolvedResourceGroup = $resourceGroup->fresh();
    $resolver = Mockery::mock(RouteResourceGroupResolver::class);
    $resolver->shouldReceive('resolve')->once()->with(
        'main-campus',
        'seminar-rooms',
        [
            'institution.closings',
            'resources.closings',
            'resources.business_hours.week_days',
            'resources.resource_group.settings',
        ],
    )->andReturn($resolvedResourceGroup);
    app()->instance(RouteResourceGroupResolver::class, $resolver);

    $request = buildRoutedFormRequest(
        CalendarEntriesRequest::class,
        'GET',
        "/{$institution->slug}/{$resourceGroup->slug}/happenings",
        [
            'start' => '2026-06-12 10:00:00',
            'end' => '2026-06-12 12:00:00',
        ],
    );
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceGroup())->toBe($resolvedResourceGroup)
        ->and($request->resourceGroup())->toBe($resolvedResourceGroup);
});
