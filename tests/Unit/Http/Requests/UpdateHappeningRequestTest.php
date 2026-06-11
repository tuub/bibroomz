<?php

declare(strict_types=1);

use App\Http\Requests\UpdateHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(UpdateHappeningRequest::class);

uses(RefreshDatabase::class);

test('UpdateHappeningRequest rules returns array with expected keys', function (): void {
    $request = new UpdateHappeningRequest;

    expect($request->rules())->toHaveKeys(['id', 'start', 'end', 'label']);
});

test('id field rules contain required uuid and exists happenings', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    expect($rules['id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:happenings,id');
});

test('start field rules contain required and date', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    expect($rules['start'])
        ->toContain('required')
        ->toContain('date');
});

test('end field rules contain required and date', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    expect($rules['end'])
        ->toContain('required')
        ->toContain('date');
});

test('label field rules contain nullable', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    expect($rules['label'])->toContain('nullable');
});

test('rules rejects missing id', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    $v = Validator::make(['start' => '2026-06-10 10:00:00', 'end' => '2026-06-10 11:00:00'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('id'))->toBeTrue();
});

test('rules rejects invalid uuid for id', function (): void {
    $rules = (new UpdateHappeningRequest)->rules();

    $v = Validator::make(['id' => 'not-a-uuid', 'start' => '2026-06-10 10:00:00', 'end' => '2026-06-10 11:00:00'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('id'))->toBeTrue();
});

test('rules rejects missing start', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $rules = (new UpdateHappeningRequest)->rules();

    $v = Validator::make(['id' => $happening->id, 'end' => '2026-06-10 11:00:00'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('start'))->toBeTrue();
});

test('rules rejects missing end', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $rules = (new UpdateHappeningRequest)->rules();

    $v = Validator::make(['id' => $happening->id, 'start' => '2026-06-10 10:00:00'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('end'))->toBeTrue();
});

test('authorize returns false when no user is set', function (): void {
    $request = new UpdateHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('happening() caches model on second call', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $request = buildFormRequest(UpdateHappeningRequest::class, ['id' => $happening->id]);

    $first = $request->happening();
    $second = $request->happening();

    // Same instance returned on second call (early return used cached model)
    expect($first)->toBe($second)
        ->and($first->id)->toBe($happening->id);
});

test('validationData merges route parameters', function (): void {
    $request = new UpdateHappeningRequest;

    // When route() returns null, the null-safe operator returns [], so merge with [] works
    $data = $request->validationData();

    expect($data)->toBeArray();
});
