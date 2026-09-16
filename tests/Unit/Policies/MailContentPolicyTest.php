<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Policies\MailContentPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

covers(MailContentPolicy::class);

uses(LazilyRefreshDatabase::class);

test('MailContentPolicy viewAny returns bool for user and institution', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $institution = Institution::factory()->create();
    $policy = new MailContentPolicy;

    expect($policy->viewAny($user, $institution))->toBeBool();
});
