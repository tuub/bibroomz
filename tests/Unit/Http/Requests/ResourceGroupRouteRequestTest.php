<?php

declare(strict_types=1);

use App\Http\Requests\ResourceGroupRouteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ResourceGroupRouteRequest::class);

uses(RefreshDatabase::class);

test('ResourceGroupRouteRequest defines validation rules', function (): void {
    $request = new ResourceGroupRouteRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('institution_slug')
        ->and($rules['institution_slug'])->toContain('required')
        ->and($rules['institution_slug'])->toContain('string')
        ->and($rules)->toHaveKey('resource_group_slug')
        ->and($rules['resource_group_slug'])->toContain('required')
        ->and($rules['resource_group_slug'])->toContain('string');
});

test('ResourceGroupRouteRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new ResourceGroupRouteRequest;

    expect($request->authorize())->toBeTrue();
});

test('validationData keeps request payload when no route is bound', function (): void {
    $request = buildFormRequest(ResourceGroupRouteRequest::class, [
        'institution_slug' => 'main-campus',
        'resource_group_slug' => 'seminar-rooms',
    ]);

    expect($request->validationData())->toBe([
        'institution_slug' => 'main-campus',
        'resource_group_slug' => 'seminar-rooms',
    ]);
});

test('institutionSlug returns an empty string when the validated value is not a string', function (): void {
    $request = buildFormRequest(ResourceGroupRouteRequest::class, [
        'institution_slug' => ['invalid'],
        'resource_group_slug' => 'seminar-rooms',
    ]);
    $validator = Validator::make($request->all(), [
        'institution_slug' => ['array'],
        'resource_group_slug' => ['string'],
    ]);
    $validator->passes();
    $request->setValidator($validator);

    expect($request->institutionSlug())->toBe('');
});

test('resourceGroupSlug returns an empty string when the validated value is not a string', function (): void {
    $request = buildFormRequest(ResourceGroupRouteRequest::class, [
        'institution_slug' => 'main-campus',
        'resource_group_slug' => ['invalid'],
    ]);
    $validator = Validator::make($request->all(), [
        'institution_slug' => ['string'],
        'resource_group_slug' => ['array'],
    ]);
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceGroupSlug())->toBe('');
});
