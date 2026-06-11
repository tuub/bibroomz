<?php

declare(strict_types=1);

use App\Http\Requests\Admin\SettingIdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(SettingIdRequest::class);

uses(RefreshDatabase::class);

test('SettingIdRequest defines validation rules', function (): void {
    $request = new SettingIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:settings,id');
});

test('SettingIdRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new SettingIdRequest;

    expect($request->authorize())->toBeTrue();
});
