<?php

declare(strict_types=1);

use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithPermissions;

covers(UpdateClosingRequest::class);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('rules include all required update closing fields', function (): void {
    $request = buildFormRequest(UpdateClosingRequest::class, []);
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules)->toHaveKey('closable_id')
        ->and($rules)->toHaveKey('closable_type')
        ->and($rules)->toHaveKey('start_date')
        ->and($rules)->toHaveKey('start_time')
        ->and($rules)->toHaveKey('end_date')
        ->and($rules)->toHaveKey('end_time')
        ->and($rules)->toHaveKey('description');
});

test('id field rules contain required and uuid', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['id'])
        ->toContain('required')
        ->toContain('uuid');
});

test('closable_id field rules contain required and uuid', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['closable_id'])
        ->toContain('required')
        ->toContain('uuid');
});

test('closable_type field rules contain required and string', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['closable_type'])
        ->toContain('required')
        ->toContain('string');
});

test('start_date field rules contain required and date_format:d.m.Y', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['start_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('start_time field rules contain required and date_format:H:i', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['start_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('end_date field rules contain required and date_format:d.m.Y', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['end_date'])
        ->toContain('required')
        ->toContain('date_format:d.m.Y');
});

test('end_time field rules contain required and date_format:H:i', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    expect($rules['end_time'])
        ->toContain('required')
        ->toContain('date_format:H:i');
});

test('description keeps the exact empty-string placeholder rule', function (): void {
    expect(buildFormRequest(UpdateClosingRequest::class, [])->rules()['description'])->toBe(['']);
});

test('id is required', function (): void {
    $institution = Institution::factory()->create();
    $rules = buildFormRequest(UpdateClosingRequest::class, [
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
    ])->rules();

    $validator = Validator::make([
        'closable_id' => $institution->id,
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('id must be a uuid', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => 'not-a-uuid',
        'closable_id' => (string) Str::uuid(),
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('id'))->toBeTrue();
});

test('closable_id must be a uuid', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
        'closable_id' => 'not-a-uuid',
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('closable_id'))->toBeTrue();
});

test('start_date requires d.m.Y format', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
        'closable_id' => (string) Str::uuid(),
        'closable_type' => 'institution',
        'start_date' => '2026-06-10',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_date'))->toBeTrue();
});

test('closable_type is required', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
        'closable_id' => (string) Str::uuid(),
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('closable_type'))->toBeTrue();
});

test('start_time rejects H:i:s format', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
        'closable_id' => (string) Str::uuid(),
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('start_time'))->toBeTrue();
});

test('end_time rejects H:i:s format', function (): void {
    $rules = buildFormRequest(UpdateClosingRequest::class, [])->rules();

    $validator = Validator::make([
        'id' => (string) Str::uuid(),
        'closable_id' => (string) Str::uuid(),
        'closable_type' => 'institution',
        'start_date' => '10.06.2026',
        'start_time' => '09:00',
        'end_date' => '10.06.2026',
        'end_time' => '10:00:00',
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('end_time'))->toBeTrue();
});

test('authorize returns false when no user is authenticated', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $request = buildFormRequest(UpdateClosingRequest::class, ['id' => $closing->id]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when closing not found', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(UpdateClosingRequest::class, [], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns false when user lacks edit_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Maintenance'],
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(UpdateClosingRequest::class, ['id' => $closing->id], $user);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when user has edit_closings permission', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Maintenance'],
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_closings');

    $request = buildAdminFormRequest(UpdateClosingRequest::class, ['id' => $closing->id], $user);

    expect($request->authorize())->toBeTrue();
});

test('closingOrNull returns null when no id is provided', function (): void {
    $request = buildFormRequest(UpdateClosingRequest::class, []);

    expect($request->closingOrNull())->toBeNull();
});

test('closingOrNull returns the closing model for a valid id', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);

    $request = buildFormRequest(UpdateClosingRequest::class, ['id' => $closing->id]);

    expect($request->closingOrNull()?->id)->toBe($closing->id);
});

test('closing accessor throws ModelNotFoundException when model not found', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $closing->id,
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(UpdateClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $closing->forceDelete();

    expect(fn () => $request->closing())->toThrow(ModelNotFoundException::class);
});

test('closableType and closableId accessors return validated values', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $closing->id,
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(UpdateClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closableType())->toBe(Institution::class)
        ->and($request->closableId())->toBe($institution->id);
});

test('closing accessor returns the correct model when found', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test'],
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $closing->id,
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(UpdateClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closing()->id)->toBe($closing->id);
});
