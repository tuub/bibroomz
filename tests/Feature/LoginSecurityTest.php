<?php

covers(
    App\Http\Controllers\LoginController::class,
    App\Services\Http\LoginAction::class,
    App\Auth\AlmaUserProvider::class
);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('local system user login does not fall back to alma', function () {
    $user = User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'secret-pass'])
        ->assertOk();

    $this->assertAuthenticatedAs($user);
});

test('local system user with wrong password is rejected without remote lookup', function () {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertUnauthorized();

    $this->assertGuest();
});

test('remote user cannot authenticate with stored local password', function () {
    User::factory()->create([
        'name' => 'remote-user',
        'password' => Hash::make('stored-local-password'),
        'is_system_user' => false,
    ]);

    mockLoginAlmaResponse('<result><code>1</code></result>');

    $this->postJson(route('login'), ['username' => 'remote-user', 'password' => 'stored-local-password'])
        ->assertUnauthorized();

    $this->assertGuest();
});

test('new alma users are created as directory accounts with non static passwords', function () {
    mockLoginAlmaResponse('<result><code>0</code><email_address>remote@example.org</email_address></result>');

    $this->postJson(route('login'), ['username' => 'RemoteUser', 'password' => 'remote-secret'])
        ->assertOk();

    $user = User::firstWhere('name', 'remoteuser');

    expect($user)->not->toBeNull();
    expect($user->isSystemUser())->toBeFalse();
    expect(Hash::check('Test123!', $user->password))->toBeFalse();
});

test('login is rate limited after five failed attempts', function () {
    User::factory()->create([
        'name' => 'LocalUser',
        'password' => Hash::make('secret-pass'),
        'is_system_user' => true,
    ]);

    Curl::shouldReceive('to')->never();

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
            ->assertUnauthorized();
    }

    $this->postJson(route('login'), ['username' => 'localuser', 'password' => 'wrong-pass'])
        ->assertStatus(429);
});
