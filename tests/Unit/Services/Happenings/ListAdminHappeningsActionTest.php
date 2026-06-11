<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Happenings\ListAdminHappeningsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

covers(ListAdminHappeningsAction::class);

uses(RefreshDatabase::class);

test('execute returns collection for admin user', function (): void {
    $admin = User::factory()->create(['is_admin' => true]);

    $action = app(ListAdminHappeningsAction::class);
    $result = $action->execute($admin);

    expect($result)->toBeInstanceOf(Collection::class);
});
