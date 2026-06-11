<?php

use App\Http\Requests\Admin\HappeningIdRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(HappeningIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(HappeningIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('happening accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $booker = User::factory()->create();
    $verifier = User::factory()->create(['is_system_user' => true]);

    $happening = Happening::create([
        'user_id_01' => $booker->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);

    $user = User::factory()->create();
    $request = buildAdminFormRequest(HappeningIdRequest::class, ['id' => $happening->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->happening()->id)->toBe($happening->id);
});

test('happening accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $booker = User::factory()->create();
    $verifier = User::factory()->create(['is_system_user' => true]);

    $happening = Happening::create([
        'user_id_01' => $booker->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => $verifier->name,
        'start' => now()->addHour(),
        'end' => now()->addHours(2),
        'reserved_at' => now(),
    ]);

    $user = User::factory()->create();
    $request = buildAdminFormRequest(HappeningIdRequest::class, ['id' => $happening->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $happening->forceDelete();

    expect(fn () => $request->happening())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new HappeningIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:happenings,id');
});
