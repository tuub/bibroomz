<?php

declare(strict_types=1);

use App\Models\User;
use App\Rules\CurrentPasswordRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

covers(CurrentPasswordRule::class);

uses(RefreshDatabase::class);

test('validate calls fail when password is wrong', function (): void {
    $user = User::factory()->create([
        'name' => 'testuser',
        'password' => Hash::make('correct-password'),
    ]);

    $rule = new CurrentPasswordRule('testuser', 'wrong-password');

    $failCalled = false;
    $rule->validate('current_password', 'wrong-password', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeTrue();
});

test('validate does not call fail when password is correct', function (): void {
    $user = User::factory()->create([
        'name' => 'testuser',
        'password' => Hash::make('correct-password'),
    ]);

    $rule = new CurrentPasswordRule('testuser', 'correct-password');

    $failCalled = false;
    $rule->validate('current_password', 'correct-password', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});

test('validate does not call fail when user is not found', function (): void {
    $rule = new CurrentPasswordRule('nonexistent-user', 'some-password');

    $failCalled = false;
    $rule->validate('current_password', 'some-password', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});

test('validate does not call fail when current_password is null', function (): void {
    $user = User::factory()->create([
        'name' => 'testuser',
        'password' => Hash::make('correct-password'),
    ]);

    $rule = new CurrentPasswordRule('testuser', null);

    $failCalled = false;
    $rule->validate('current_password', null, function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});
