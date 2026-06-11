<?php

declare(strict_types=1);

use App\Http\Requests\VerifyHappeningRequest;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(VerifyHappeningRequest::class);

uses(RefreshDatabase::class);

test('VerifyHappeningRequest defines validation rules', function (): void {
    $request = new VerifyHappeningRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:happenings,id')
        ->and($rules)->toHaveKey('start')
        ->and($rules['start'])->toContain('required')
        ->and($rules['start'])->toContain('date')
        ->and($rules)->toHaveKey('end')
        ->and($rules['end'])->toContain('required')
        ->and($rules['end'])->toContain('date');
});

test('VerifyHappeningRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new VerifyHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('validationData keeps request payload when no route is bound', function (): void {
    $request = buildFormRequest(VerifyHappeningRequest::class, [
        'id' => 'abc',
        'start' => '2026-06-12 10:00:00',
        'end' => '2026-06-12 11:00:00',
    ]);

    expect($request->validationData())->toBe([
        'id' => 'abc',
        'start' => '2026-06-12 10:00:00',
        'end' => '2026-06-12 11:00:00',
    ]);
});

test('happening returns the cached model on repeated calls', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'is_verified' => false,
    ]);
    $request = buildFormRequest(VerifyHappeningRequest::class, [
        'id' => $happening->id,
        'start' => '2026-06-12 10:00:00',
        'end' => '2026-06-12 11:00:00',
    ]);
    $firstResolved = $request->happening();

    $happening->forceDelete();

    expect($request->happening())->toBe($firstResolved);
});
