<?php

declare(strict_types=1);

use App\Http\Requests\Admin\HappeningRequest;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(HappeningRequest::class);

uses(RefreshDatabase::class);

test('HappeningRequest is abstract class with rules method', function (): void {
    $reflection = new ReflectionClass(HappeningRequest::class);

    expect($reflection->isAbstract())->toBeTrue()
        ->and($reflection->hasMethod('rules'))->toBeTrue();
});

test('rules contains all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $request = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ]);
    $rules = $request->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('start_date')
        ->toHaveKey('start_time')
        ->toHaveKey('end_date')
        ->toHaveKey('end_time')
        ->toHaveKey('resource_id')
        ->toHaveKey('user_id_01')
        ->toHaveKey('user_id_02')
        ->toHaveKey('verifier')
        ->toHaveKey('is_verified')
        ->toHaveKey('label');
});

test('id field rules contain sometimes nullable uuid exists happenings', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['id'])
        ->toContain('sometimes')
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:happenings,id');
});

test('start_date field rules contain required and date_format', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['start_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('start_time field rules contain required and date_format H:i', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['start_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('end_date field rules contain required and date_format', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['end_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('end_time field rules contain required and date_format H:i', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['end_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('resource_id field rules contain required uuid exists resources', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['resource_id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:resources,id');
});

test('user_id_01 field rules contain required uuid exists users', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_01'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:users,id');
});

test('user_id_02 field rules contain sometimes nullable uuid exists users', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_02'])
        ->toContain('sometimes')
        ->toContain('nullable')
        ->toContain('uuid')
        ->toContain('exists:users,id');
});

test('is_verified field rules contain required and boolean', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['is_verified'])
        ->toContain('required')
        ->toContain('boolean');
});

test('user_id_02 rules contain exclude_if when verification not required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_02'])->toContain('exclude_if:is_verified,false');
});

test('verifier rules contain not_in for user name', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create(['name' => 'testuser']);

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $notInRule = collect((array) $rules['verifier'])->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'not_in:'));

    expect($notInRule)->not->toBeNull();
});

test('when verification is required verifier has required_if:is_verified,false rule', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => false,
    ])->rules();

    expect($rules['verifier'])->toContain('required_if:is_verified,false');
});

test('when verification is required user_id_02 has required_if:is_verified,true rule', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'is_verified' => true,
    ])->rules();

    expect($rules['user_id_02'])->toContain('required_if:is_verified,true');
});

test('start_date rejects Y-m-d format', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $v = Validator::make([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '2026-06-10',
        'start_time' => '10:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'is_verified' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('start_date'))->toBeTrue();
});

test('end_time rejects H:i:s format', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $v = Validator::make([
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '10.06.2026',
        'start_time' => '10:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00:00',
        'is_verified' => false,
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('end_time'))->toBeTrue();
});

test('when user1 is null isAdmin defaults to false', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
    ])->rules();

    expect($rules['verifier'])->toContain('required_if:is_verified,false');
});

test('when user1 has no_verifier permission isAdmin is true and verifier not required', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'no_verifier');

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['verifier'])->not->toContain('required_if:is_verified,false');
});

test('when user has no_verifier but verification is required isVerificationRequired is false', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    grantAdminPermission($user, $institution, 'no_verifier');

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_02'])->not->toContain('required_if:is_verified,true');
});

test('when resource is_verification_required is truthy isVerificationRequired is boolean true', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['verifier'])->toContain('required_if:is_verified,false')
        ->and($rules['user_id_02'])->toContain('required_if:is_verified,true');
});

test('when verification not required user_id_02 rule is empty string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $verificationRule = collect((array) $rules['user_id_02'])
        ->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'required_if'));

    expect($verificationRule)->toBeNull();
});

test('user_id_02 rules keep the exact placeholder entry when verification is not required', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect($rules['user_id_02'])->toBe([
        'sometimes',
        'nullable',
        'uuid',
        '',
        'exclude_if:is_verified,false',
        'exists:users,id',
    ]);
});

test('verifier rules array has at least three entries', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create(['name' => 'testverifyuser']);

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect(count((array) $rules['verifier']))->toBeGreaterThanOrEqual(3);
});

test('verifier not_in rule contains the full user name', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create(['name' => 'specificuser123']);

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    $notInRule = collect((array) $rules['verifier'])
        ->first(fn ($r): bool => is_string($r) && str_starts_with($r, 'not_in:'));

    expect($notInRule)->toBe('not_in:specificuser123');
});

test('label rules array has one entry', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();

    $rules = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
    ])->rules();

    expect(count((array) $rules['label']))->toBe(1);
});

test('sanitized passes empty string to createCarbonDateTime when startDate is not a string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();
    $request = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
        'is_verified' => false,
    ]);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);
    $sanitized = $request->sanitized();

    expect($sanitized['start'])->toBe(Utility::createCarbonDateTime('10.06.2026', '09:00')->toIsoString());
});

test('sanitized passes empty string to createCarbonDateTime when endDate is not a string', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();
    $request = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '11:00',
        'is_verified' => false,
    ]);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);
    $sanitized = $request->sanitized();

    expect($sanitized['end'])->toBe(Utility::createCarbonDateTime('10.06.2026', '11:00')->toIsoString());
});

test('sanitized start_date in sanitized output uses empty string fallback not original value (EmptyStringToNotEmpty lines 74 75)', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();
    $request = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '15.07.2026',
        'start_time' => '14:00',
        'end_date' => '15.07.2026',
        'end_time' => '15:30',
        'is_verified' => false,
    ]);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);
    $sanitized = $request->sanitized();

    expect($sanitized['start'])->not->toBe('')
        ->and($sanitized['start'])->toBe(Utility::createCarbonDateTime('15.07.2026', '14:00')->toIsoString())
        ->and($sanitized['end'])->toBe(Utility::createCarbonDateTime('15.07.2026', '15:30')->toIsoString());
});

test('sanitized returns normalized timestamps and removes the original date and time fields', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => false,
    ]);
    $user = User::factory()->create();
    $request = buildFormRequest(StoreHappeningRequest::class, [
        'resource_id' => $resource->id,
        'user_id_01' => $user->id,
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:30',
        'is_verified' => false,
        'label' => 'Board meeting',
    ]);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);
    $sanitized = $request->sanitized();

    expect($sanitized['start'])->toBe(Utility::createCarbonDateTime('10.06.2026', '09:00')->toIsoString())
        ->and($sanitized['end'])->toBe(Utility::createCarbonDateTime('10.06.2026', '10:30')->toIsoString())
        ->and($sanitized)->not->toHaveKey('start_date')
        ->and($sanitized)->not->toHaveKey('start_time')
        ->and($sanitized)->not->toHaveKey('end_date')
        ->and($sanitized)->not->toHaveKey('end_time')
        ->and($sanitized['resource_id'])->toBe($resource->id)
        ->and($sanitized['user_id_01'])->toBe($user->id)
        ->and($sanitized['is_verified'])->toBeFalse()
        ->and($sanitized['label'])->toBe('Board meeting');
});
