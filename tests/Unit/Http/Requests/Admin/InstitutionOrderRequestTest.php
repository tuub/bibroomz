<?php

declare(strict_types=1);

use App\Http\Requests\Admin\InstitutionOrderRequest;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

covers(InstitutionOrderRequest::class);

uses(RefreshDatabase::class);

test('InstitutionOrderRequest defines validation rules', function (): void {
    $request = new InstitutionOrderRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('*.id')
        ->and($rules['*.id'])->toContain('required')
        ->and($rules['*.id'])->toContain('uuid')
        ->and($rules['*.id'])->toContain('exists:institutions,id')
        ->and($rules)->toHaveKey('*.order')
        ->and($rules['*.order'])->toContain('required')
        ->and($rules['*.order'])->toContain('integer');
});

test('InstitutionOrderRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new InstitutionOrderRequest;

    expect($request->authorize())->toBeFalse();
});

test('rows returns parsed id-order pairs', function (): void {
    $institution = Institution::factory()->create();

    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => $institution->id, 'order' => 1],
    ]);

    $rows = $request->rows();

    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe($institution->id);
});

test('rows handles numeric string order', function (): void {
    $institution = Institution::factory()->create();

    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => $institution->id, 'order' => '7'],
    ]);

    expect($request->rows()->first()['order'])->toBe(7);
});

test('rows filters out non-array entries', function (): void {
    $request = buildFormRequest(InstitutionOrderRequest::class, ['not-an-array']);

    expect($request->rows()->count())->toBe(0);
});

test('rows filters out array entries without id key', function (): void {
    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['order' => 1],
    ]);

    expect($request->rows()->count())->toBe(0);
});

test('rows maps non-string id to empty string', function (): void {
    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => 99, 'order' => 1],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['id'])->toBe('');
});

test('rows maps non-numeric string order to 0', function (): void {
    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => 'some-id', 'order' => 'nope'],
    ]);

    $rows = $request->rows();
    expect($rows->count())->toBe(1)
        ->and($rows->first()['order'])->toBe(0);
});

test('authorize returns false when no user', function (): void {
    $institution = Institution::factory()->create();

    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => $institution->id, 'order' => 1],
    ]);

    expect($request->authorize())->toBeFalse();
});

test('authorize returns true when admin user can update all institutions', function (): void {
    $institution = Institution::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => $institution->id, 'order' => 1],
    ], $admin);

    expect($request->authorize())->toBeTrue();
});

test('authorize returns false when institution not found', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $request = buildFormRequest(InstitutionOrderRequest::class, [
        ['id' => (string) Str::uuid(), 'order' => 1],
    ], $admin);

    expect($request->authorize())->toBeFalse();
});
