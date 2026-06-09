<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(User::class);

uses(RefreshDatabase::class);

test('user factory sets security flags to false by default', function (): void {
    $user = User::factory()->make();

    expect($user->is_admin)->toBeFalse()
        ->and($user->is_system_user)->toBeFalse()
        ->and($user->is_logged_in)->toBeFalse();
});
