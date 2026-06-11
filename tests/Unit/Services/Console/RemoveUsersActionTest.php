<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Console\RemoveUsersAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(RemoveUsersAction::class);

uses(RefreshDatabase::class);

test('execute deletes all users in collection', function (): void {
    $users = User::factory()->count(3)->create();
    $ids = $users->pluck('id')->all();

    $action = new RemoveUsersAction;
    $action->execute($users);

    foreach ($ids as $id) {
        expect(User::find($id))->toBeNull();
    }
});

test('execute handles empty collection', function (): void {
    $action = new RemoveUsersAction;
    $action->execute(User::whereKey([])->get());

    expect(User::count())->toBe(0);
});
