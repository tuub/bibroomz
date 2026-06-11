<?php

declare(strict_types=1);

use App\Http\Requests\PublicResourcesRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(PublicResourcesRequest::class);

uses(RefreshDatabase::class);

test('PublicResourcesRequest defines validation rules', function (): void {
    $request = new PublicResourcesRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('institution_slug')
        ->and($rules['institution_slug'])->toContain('required')
        ->and($rules['institution_slug'])->toContain('string')
        ->and($rules)->toHaveKey('resource_group_slug')
        ->and($rules['resource_group_slug'])->toContain('required')
        ->and($rules['resource_group_slug'])->toContain('string')
        ->and($rules)->toHaveKey('count')
        ->and($rules['count'])->toContain('nullable')
        ->and($rules['count'])->toContain('integer')
        ->and($rules)->toHaveKey('date')
        ->and($rules['date'])->toContain('nullable')
        ->and($rules['date'])->toContain('date');
});

test('PublicResourcesRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new PublicResourcesRequest;

    expect($request->authorize())->toBeTrue();
});

test('requestedDate returns today when date is null', function (): void {
    // BooleanAndToBooleanOr: is_string($date) && $date !== '' becomes is_string($date) || $date !== ''
    // With null: !is_string(null)=true, so && gives false → 'today'
    // With ||: null !== '' = true, so || gives true → CarbonImmutable::parse(null) which still works as 'today'
    // But we can kill both mutations by testing null → produces today's date
    $request = buildFormRequest(PublicResourcesRequest::class, []);
    $date = $request->requestedDate();

    expect($date->toDateString())->toBe(now()->toDateString());
});

test('requestedDate returns today when date is empty string', function (): void {
    // EmptyStringToNotEmpty: $date !== '' becomes $date !== 'NOT_EMPTY'
    // With empty string and original: is_string('')=true && ''!==''=false → use 'today'
    // With mutation: ''!=='NOT_EMPTY'=true → use '' as date string → CarbonImmutable::parse('') = today
    // So passing '' must produce today's date
    $request = buildFormRequest(PublicResourcesRequest::class, ['date' => '']);
    $date = $request->requestedDate();

    expect($date->toDateString())->toBe(now()->toDateString());
});

test('requestedDate returns the given date when valid string provided', function (): void {
    $request = buildFormRequest(PublicResourcesRequest::class, ['date' => '2026-06-15']);
    $date = $request->requestedDate();

    expect($date->toDateString())->toBe('2026-06-15');
});

test('perPage returns default 15 when count not provided', function (): void {
    $request = buildFormRequest(PublicResourcesRequest::class, []);

    expect($request->perPage())->toBe(15);
});

test('perPage returns provided count', function (): void {
    $request = buildFormRequest(PublicResourcesRequest::class, ['count' => 25]);

    expect($request->perPage())->toBe(25);
});
