<?php

declare(strict_types=1);

use App\Http\Requests\Admin\PermissionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(PermissionRequest::class);

uses(RefreshDatabase::class);

test('PermissionRequest defines validation rules', function (): void {
    $request = new PermissionRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('name')
        ->and($rules)->toHaveKey('description');
});

test('name key in rules contains RequiredWithTranslationRule object', function (): void {
    // RemoveArrayItem would remove the name entry from rules
    $rules = (new PermissionRequest)->rules();

    expect($rules)->toHaveKey('name');
    expect($rules['name'])->not->toBeEmpty();
});

test('description is optional so request passes without it', function (): void {
    // EmptyStringToNotEmpty would make '' into something like 'required'
    // A request without description must still pass
    $rules = (new PermissionRequest)->rules();

    $validator = Validator::make(
        ['name' => ['en' => 'Test Permission']],
        $rules,
    );

    expect($validator->passes())->toBeTrue();
});

test('description keeps the exact empty-string placeholder rule', function (): void {
    expect((new PermissionRequest)->rules()['description'])->toBe(['']);
});
