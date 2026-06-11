<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Console\RemoveUsersQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RemoveUsersQueryBuilder::class);

uses(RefreshDatabase::class);

test('build returns a query builder', function (): void {
    $builder = new RemoveUsersQueryBuilder;
    $query = $builder->build(30);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('candidates returns collection of non-logged-in users', function (): void {
    $builder = new RemoveUsersQueryBuilder;
    $candidates = $builder->candidates(30);

    expect($candidates)->toBeInstanceOf(Collection::class);
});

test('build excludes admin users', function (): void {
    User::factory()->create(['is_admin' => true]);
    $builder = new RemoveUsersQueryBuilder;
    $query = $builder->build(30);

    $users = $query->get();
    expect($users->where('is_admin', true)->count())->toBe(0);
});
