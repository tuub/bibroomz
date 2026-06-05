<?php

covers(App\Models\User::class);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user factory sets security flags to false by default', function () {
    $user = User::factory()->make();

    expect($user->is_admin)->toBeFalse()
        ->and($user->is_system_user)->toBeFalse()
        ->and($user->is_logged_in)->toBeFalse();
});
