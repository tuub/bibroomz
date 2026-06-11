<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ImportUsersRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(ImportUsersRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('authorize returns false when no authenticated user', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group 1'),
        'institution_id' => $institution->id,
    ]);
    $request = buildFormRequest(ImportUsersRequest::class, ['id' => $userGroup->id, 'users' => []]);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user group not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(ImportUsersRequest::class, ['users' => []], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group 2'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ImportUsersRequest::class, ['id' => $userGroup->id, 'users' => []], $user);
    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_user_groups permission', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group 3'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(ImportUsersRequest::class, ['id' => $userGroup->id, 'users' => []], $user);
    expect($request->authorize())->toBeTrue();
});

test('rules contains all expected keys', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules)
        ->toHaveKey('id')
        ->toHaveKey('users')
        ->toHaveKey('users.*.name')
        ->toHaveKey('valid_from_date')
        ->toHaveKey('valid_until_date')
        ->toHaveKey('valid_from_text')
        ->toHaveKey('valid_until_text');
});

test('id field rules contain required uuid exists user_groups', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['id'])
        ->toContain('required')
        ->toContain('uuid')
        ->toContain('exists:user_groups,id');
});

test('users field rules contain required and array', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['users'])
        ->toContain('required')
        ->toContain('array');
});

test('users star name field rules contain required and string', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['users.*.name'])
        ->toContain('required')
        ->toContain('string');
});

test('valid_from_date field rules contain nullable date prohibits valid_from_text', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['valid_from_date'])
        ->toContain('nullable')
        ->toContain('date')
        ->toContain('prohibits:valid_from_text');
});

test('valid_until_date field rules contain nullable date prohibits valid_until_text', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['valid_until_date'])
        ->toContain('nullable')
        ->toContain('date')
        ->toContain('prohibits:valid_until_text');
});

test('valid_from_text field rules contain nullable and prohibits valid_from_date', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['valid_from_text'])
        ->toContain('nullable')
        ->toContain('prohibits:valid_from_date');
});

test('valid_until_text field rules contain nullable and prohibits valid_until_date', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    expect($rules['valid_until_text'])
        ->toContain('nullable')
        ->toContain('prohibits:valid_until_date');
});

test('valid_until_text runs the custom locale-aware date validator closure', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $validator = Validator::make([
        'id' => $group->id,
        'users' => [['name' => 'Alice']],
        'valid_until_text' => 'this-is-not-a-date-interval',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('valid_until_text'))->toBeTrue();
});

test('id is required', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make(['users' => [['name' => 'Alice']]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('id'))->toBeTrue();
});

test('id rejects non-uuid', function (): void {
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make(['id' => 'bad', 'users' => [['name' => 'Alice']]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('id'))->toBeTrue();
});

test('users is required', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make(['id' => $group->id], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('users'))->toBeTrue();
});

test('users must be array', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make(['id' => $group->id, 'users' => 'bad'], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('users'))->toBeTrue();
});

test('users star name is required', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make(['id' => $group->id, 'users' => [[]]], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('users.0.name'))->toBeTrue();
});

test('valid_from_date rejects non-date', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make([
        'id' => $group->id,
        'users' => [['name' => 'Alice']],
        'valid_from_date' => 'not-a-date',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('valid_from_date'))->toBeTrue();
});

test('valid_from_date prohibits valid_from_text', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = (new ImportUsersRequest)->rules();

    $v = Validator::make([
        'id' => $group->id,
        'users' => [['name' => 'Alice']],
        'valid_from_date' => '2026-01-01',
        'valid_from_text' => 'January 2026',
    ], $rules);

    expect($v->fails())->toBeTrue()
        ->and($v->errors()->has('valid_from_date'))->toBeTrue();
});

test('userGroup accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group 4'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(ImportUsersRequest::class, [
        'id' => $userGroup->id,
        'users' => [['name' => 'Test User']],
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->userGroup()->id)->toBe($userGroup->id);
});

test('userGroupOrNull accessor returns the model when found', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Import Group 5'),
        'institution_id' => $institution->id,
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ImportUsersRequest::class, ['id' => $userGroup->id, 'users' => []], $user);

    expect($request->userGroupOrNull())->not->toBeNull();
    expect($request->userGroupOrNull()?->id)->toBe($userGroup->id);
});

test('userGroupOrNull accessor returns null when not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(ImportUsersRequest::class, ['users' => []], $user);

    expect($request->userGroupOrNull())->toBeNull();
});

test('passedValidation uses CarbonImmutable now when neither valid_from_date nor valid_from_text is provided', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);

    $input = [
        'id' => $group->id,
        'users' => [['name' => 'alice']],
    ];

    $request = buildFormRequest(ImportUsersRequest::class, $input);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $reflection = new ReflectionMethod($request, 'passedValidation');
    $reflection->invoke($request);

    $data = $request->importData();

    expect($data)->toHaveKey('valid_from')
        ->and($data['valid_until'])->toBeNull();
});

test('passedValidation parses valid_from_text elseif branch', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);

    $input = [
        'id' => $group->id,
        'users' => [['name' => 'alice']],
        'valid_from_text' => '2026-01-01',
    ];

    $request = buildFormRequest(ImportUsersRequest::class, $input);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $reflection = new ReflectionMethod($request, 'passedValidation');
    $reflection->invoke($request);

    $data = $request->importData();

    expect($data)->toHaveKey('valid_from')
        ->and($data['valid_until'])->toBeNull();
});

test('passedValidation uses valid_from_date and valid_until_date', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);

    $input = [
        'id' => $group->id,
        'users' => [['name' => 'alice']],
        'valid_from_date' => '2026-01-15',
        'valid_until_date' => '2026-12-31',
    ];

    $request = buildFormRequest(ImportUsersRequest::class, $input);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $reflection = new ReflectionMethod($request, 'passedValidation');
    $reflection->invoke($request);

    $data = $request->importData();

    expect($data)->toHaveKey('valid_from')
        ->and($data)->toHaveKey('valid_until')
        ->and($data['valid_from']->toDateString())->toBe('2026-01-15')
        ->and($data['valid_until']?->toDateString())->toBe('2026-12-31');
});

test('passedValidation computes valid_until from valid_until_text interval', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);

    $input = [
        'id' => $group->id,
        'users' => [['name' => 'alice']],
        'valid_from_date' => '2026-01-01',
        'valid_until_text' => '1 month',
    ];

    $request = buildFormRequest(ImportUsersRequest::class, $input);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $reflection = new ReflectionMethod($request, 'passedValidation');
    $reflection->invoke($request);

    $data = $request->importData();

    expect($data)->toHaveKey('valid_from')
        ->and($data['valid_until'])->not->toBeNull();
});

test('importData returns merged safe data including valid_from', function (): void {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);

    $input = [
        'id' => $group->id,
        'users' => [['name' => 'alice']],
    ];
    $request = buildFormRequest(ImportUsersRequest::class, $input);

    $validator = Validator::make($request->all(), $request->rules());
    $validator->passes();

    $request->setValidator($validator);

    $request->merge([
        'valid_from' => CarbonImmutable::parse('2026-06-01'),
        'valid_until' => null,
    ]);

    $data = $request->importData();

    expect($data)->toHaveKey('id')
        ->and($data)->toHaveKey('valid_from');
});
