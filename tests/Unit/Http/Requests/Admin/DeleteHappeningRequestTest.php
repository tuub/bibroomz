<?php

declare(strict_types=1);

use App\Http\Requests\Admin\DeleteHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(DeleteHappeningRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('DeleteHappeningRequest defines validation rules', function (): void {
    $request = new DeleteHappeningRequest;

    expect($request->rules())->toBeArray();
});

test('DeleteHappeningRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new DeleteHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('rules returns all required id validation rules', function (): void {
    $request = new DeleteHappeningRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:happenings,id');
});

test('authorize returns false when happening is null', function (): void {
    // InstanceOfToTrue: $happening instanceof Model becomes true, meaning null is treated as valid.
    // Kill it: with no happening in request, authorize must return false.
    $user = User::factory()->create(['is_admin' => true]);
    $request = buildAdminFormRequest(DeleteHappeningRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user is null', function (): void {
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

    // No authenticated user
    $request = buildFormRequest(DeleteHappeningRequest::class, ['id' => $happening->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has adminDelete permission', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'delete_happenings');
    $happening = Happening::create([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'is_verified' => false,
        'reserved_at' => now(),
    ]);

    $request = buildAdminFormRequest(DeleteHappeningRequest::class, ['id' => $happening->id], $user);

    expect($request->authorize())->toBeTrue();
});
