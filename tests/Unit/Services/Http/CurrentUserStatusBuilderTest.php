<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Http\CurrentUserStatusBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(CurrentUserStatusBuilder::class);

uses(RefreshDatabase::class);

test('build returns isAdmin false for regular user', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $builder = app(CurrentUserStatusBuilder::class);
    $result = $builder->build($user);

    expect($result['isAdmin'])->toBeFalse();
});

test('build returns isAdmin true for admin user', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $builder = app(CurrentUserStatusBuilder::class);
    $result = $builder->build($user);

    expect($result['isAdmin'])->toBeTrue();
});

test('build includes user id, name, and email', function (): void {
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

    $builder = app(CurrentUserStatusBuilder::class);
    $result = $builder->build($user);

    expect($result['user'])->toHaveKey('id')
        ->and($result['user']['name'])->toBe('Test User')
        ->and($result['user']['email'])->toBe('test@example.com');
});

test('build returns allowedResourceGroups as collection', function (): void {
    $institution = Institution::factory()->create();
    ResourceGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();

    $builder = app(CurrentUserStatusBuilder::class);
    $result = $builder->build($user);

    expect($result['allowedResourceGroups'])->toBeInstanceOf(Collection::class);
});

test('build returns permissions as collection', function (): void {
    $user = User::factory()->create(['is_admin' => false]);

    $builder = app(CurrentUserStatusBuilder::class);
    $result = $builder->build($user);

    expect($result['permissions'])->toBeInstanceOf(Collection::class);
});
