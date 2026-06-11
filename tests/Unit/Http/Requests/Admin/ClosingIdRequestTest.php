<?php

use App\Http\Requests\Admin\ClosingIdRequest;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(ClosingIdRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(ClosingIdRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('closing accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test closing'],
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ClosingIdRequest::class, ['id' => $closing->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closing()->id)->toBe($closing->id);
});

test('closing accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::create([
        'closable_type' => Institution::class,
        'closable_id' => $institution->id,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => ['en' => 'Test closing'],
    ]);
    $user = User::factory()->create();

    $request = buildAdminFormRequest(ClosingIdRequest::class, ['id' => $closing->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $closing->forceDelete();

    expect(fn () => $request->closing())->toThrow(ModelNotFoundException::class);
});

test('rules returns all required id validation rules', function (): void {
    $request = new ClosingIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:closings,id');
});
