<?php

use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(InstitutionContextRequest::class);

uses(RefreshDatabase::class);

test('authorize returns true', function (): void {
    $user = User::factory()->create();
    $request = buildAdminFormRequest(InstitutionContextRequest::class, [], $user);
    expect($request->authorize())->toBeTrue();
});

test('institution accessor returns the correct model', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(InstitutionContextRequest::class, ['institution_id' => $institution->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id);
});

test('institution accessor throws when model not found', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(InstitutionContextRequest::class, ['institution_id' => $institution->id], $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    $institution->delete();

    expect(fn () => $request->institution())->toThrow(ModelNotFoundException::class);
});

test('rules returns institution_id validation rules', function (): void {
    $request = new InstitutionContextRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('institution_id')
        ->and($rules['institution_id'])->toContain('required')
        ->and($rules['institution_id'])->toContain('uuid')
        ->and($rules['institution_id'])->toContain('exists:institutions,id');
});
