<?php

declare(strict_types=1);

use App\Http\Requests\DeleteHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(DeleteHappeningRequest::class);

uses(RefreshDatabase::class);

test('DeleteHappeningRequest defines validation rules', function (): void {
    $request = new DeleteHappeningRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:happenings,id');
});

test('DeleteHappeningRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('happening caches model on second call', function (): void {
    // InstanceOfToFalse would always skip the cached model.
    // RemoveEarlyReturn would not return early and would re-query.
    // Both are killed by verifying the returned object IS the cached instance.
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $request = buildFormRequest(DeleteHappeningRequest::class, ['id' => $happening->id]);
    $first = $request->happening();
    $second = $request->happening();

    // sameObject means the cache returned the same instance
    expect($first)->toBe($second);
});

test('validationData merges route parameters without null-safe operator error', function (): void {
    // RemoveNullSafeOperator would call $this->route()->parameters() without null check.
    // When there is no route() binding, calling ->parameters() on null throws.
    // This test ensures that without a route the method returns data without error.
    $request = buildFormRequest(DeleteHappeningRequest::class, ['id' => 'some-id']);

    // Should not throw even without a route binding
    $data = $request->validationData();

    expect($data)->toHaveKey('id');
});
