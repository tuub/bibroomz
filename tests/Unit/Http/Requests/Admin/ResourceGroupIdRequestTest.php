<?php

declare(strict_types=1);

use App\Http\Requests\Admin\ResourceGroupIdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ResourceGroupIdRequest::class);

uses(RefreshDatabase::class);

test('ResourceGroupIdRequest defines validation rules', function (): void {
    $request = new ResourceGroupIdRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKey('id')
        ->and($rules['id'])->toContain('required')
        ->and($rules['id'])->toContain('uuid')
        ->and($rules['id'])->toContain('exists:resource_groups,id');
});

test('ResourceGroupIdRequest authorize requires admin', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);
    $request = new ResourceGroupIdRequest;

    expect($request->authorize())->toBeTrue();
});
