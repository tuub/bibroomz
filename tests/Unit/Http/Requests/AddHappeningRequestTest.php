<?php

declare(strict_types=1);

use App\Http\Requests\AddHappeningRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(AddHappeningRequest::class);

uses(RefreshDatabase::class);

test('add happening request authorize returns false for guest', function (): void {
    $request = new AddHappeningRequest;

    expect($request->authorize())->toBeFalse();
});

test('add happening request is a form request', function (): void {
    $request = new AddHappeningRequest;

    expect($request)->toBeInstanceOf(FormRequest::class);
});

test('rules returns array with all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user);
    $rules = $request->rules();

    expect($rules)
        ->toHaveKey('resource')
        ->toHaveKey('resource.id')
        ->toHaveKey('start')
        ->toHaveKey('end')
        ->toHaveKey('verifier')
        ->toHaveKey('label');
});

test('resource field rules contain required and array', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['resource'])
        ->toContain('required')
        ->toContain('array');
});

test('resource id field rules contain required uuid exists resources', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['resource.id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resources,id');
});

test('start field rules contain required and date', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['start'])
        ->toContain('required')
        ->toContain('date');
});

test('end field rules contain required and date', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['end'])
        ->toContain('required')
        ->toContain('date');
});

test('verifier field rules contain string when verification not required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['verifier'])
        ->toContain('string');
});

test('verifier field rules contain nullable when verification not required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['verifier'])->toContain('nullable');
});

test('verifier field contains not_in rule for user name', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create(['name' => 'testuser']);

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $notInRule = collect((array) $rules['verifier'])->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'not_in:'));

    expect($notInRule)->not->toBeNull();
});

test('verifier field rules contain required when verification is required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['verifier'])->toContain('required');
});

test('label field rules contain nullable', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    expect($rules['label'])->toContain('nullable');
});

test('resource is required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $v = Validator::make([
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'verifier' => null,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('resource'))->toBeTrue();
});

test('start is required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $v = Validator::make([
        'resource' => ['id' => $resource->id],
        'end' => '2026-06-10 11:00:00',
        'verifier' => null,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('start'))->toBeTrue();
});

test('end is required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $v = Validator::make([
        'resource' => ['id' => $resource->id],
        'start' => '2026-06-10 10:00:00',
        'verifier' => null,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('end'))->toBeTrue();
});

test('resource id must be uuid', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $v = Validator::make([
        'resource' => ['id' => 'not-a-uuid'],
        'start' => '2026-06-10 10:00:00',
        'end' => '2026-06-10 11:00:00',
        'verifier' => null,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('resource.id'))->toBeTrue();
});

test('prepareForValidation normalizes verifier string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $resource->id],
        'verifier' => 'Test User',
    ], $user);

    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->input('verifier'))->toBe(Utility::normalizeLoginName('Test User'));
});

test('prepareForValidation sets verifier to null when not a string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $resource->id],
    ], $user);

    (new ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

    expect($request->input('verifier'))->toBeNull();
});

// --- Mutation-killing tests ---

test('not_in rule contains normalized user name', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create(['name' => 'alice.smith']);
    $normalizedName = Utility::normalizeLoginName($user->name);

    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user)->rules();

    $notInRule = collect((array) $rules['verifier'])->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'not_in:'));

    // The concat must produce 'not_in:alice.smith' (right side must be appended)
    expect($notInRule)->toBe('not_in:'.$normalizedName);
});

test('normalizedUserName is null when no user', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);

    // No user → $user instanceof User is false → normalizedUserName = null
    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], null)->rules();

    // not_in rule should be 'not_in:' (with null appended = 'not_in:')
    $notInRule = collect((array) $rules['verifier'])->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'not_in:'));
    expect($notInRule)->toBe('not_in:');
});

test('isVerificationRequired is false when no user even if resource requires it', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);

    // No user → $user instanceof User is false → isVerificationRequired must be false
    $rules = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], null)->rules();

    // verifier must be 'nullable' (not 'required')
    expect($rules['verifier'])->toContain('nullable')
        ->and($rules['verifier'])->not->toContain('required');
});

test('resource method caches model on second call', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, ['resource' => ['id' => $resource->id]], $user);

    $first = $request->resource();
    $second = $request->resource();

    // Must return same instance (cached)
    expect($first)->toBe($second)
        ->and($first->id)->toBe($resource->id);
});

test('verifier returns null when value is empty string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $resource->id],
        'verifier' => '',
    ], $user);

    expect($request->verifier())->toBeNull();
});

test('verifier returns null when value is not a string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $resource->id],
        // verifier not set → null
    ], $user);

    expect($request->verifier())->toBeNull();
});

test('verifier returns string when valid non-empty string provided', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(AddHappeningRequest::class, [
        'resource' => ['id' => $resource->id],
        'verifier' => 'john.doe',
    ], $user);

    expect($request->verifier())->toBe('john.doe');
});
