<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ClosableContextRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(ClosableContextRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(ClosableContextRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('closableType returns the validated input string', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    $request = buildAdminFormRequest(ClosableContextRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $uuid,
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closableType())->toBe('institution');
});

test('closableId returns the validated UUID string', function (): void {
    $user = User::factory()->create();
    $uuid = (string) Str::uuid();

    $request = buildAdminFormRequest(ClosableContextRequest::class, [
        'closable_type' => 'resource',
        'closable_id' => $uuid,
    ], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closableId())->toBe($uuid);
});

test('rules returns closable_type and closable_id validation rules', function (): void {
    $request = new ClosableContextRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('closable_type')
        ->and($rules['closable_type'])->toContain('required')
        ->and($rules['closable_type'])->toContain('string')
        ->and($rules)->toHaveKey('closable_id')
        ->and($rules['closable_id'])->toContain('required')
        ->and($rules['closable_id'])->toContain('uuid');
});
