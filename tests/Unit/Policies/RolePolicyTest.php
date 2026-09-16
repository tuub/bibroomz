<?php

declare(strict_types=1);

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

covers(RolePolicy::class);

uses(LazilyRefreshDatabase::class);

test('RolePolicy viewAny returns bool for user without permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $policy = new RolePolicy;

    expect($policy->viewAny($user))->toBeBool();
});
