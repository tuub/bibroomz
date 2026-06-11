<?php

declare(strict_types=1);

use App\Http\Requests\Admin\PermissionGroupRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(PermissionGroupRequest::class);

uses(RefreshDatabase::class);

test('PermissionGroupRequest defines validation rules', function (): void {
    $request = new PermissionGroupRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('description');
});

test('name key in rules is non-empty', function (): void {
    $rules = (new PermissionGroupRequest)->rules();

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->not->toBeEmpty();
});

test('description is optional so validation passes without it', function (): void {
    $rules = (new PermissionGroupRequest)->rules();

    $validator = Validator::make(
        ['name' => ['en' => 'Test Group']],
        $rules,
    );

    expect($validator->passes())->toBeTrue();
});

test('description keeps the exact empty-string placeholder rule', function (): void {
    expect((new PermissionGroupRequest)->rules()['description'])->toBe(['']);
});
