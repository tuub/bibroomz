<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Policies\InstitutionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(InstitutionPolicy::class);

uses(RefreshDatabase::class);

test('InstitutionPolicy create returns bool for user without permissions', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $policy = new InstitutionPolicy;

    expect($policy->create($user))->toBeBool();
});

test('InstitutionPolicy view returns bool for user and institution', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $institution = Institution::factory()->create();
    $policy = new InstitutionPolicy;

    expect($policy->view($user, $institution))->toBeBool();
});
