<?php

declare(strict_types=1);

use App\Providers\BroadcastServiceProvider;

covers(BroadcastServiceProvider::class);

test('broadcast service provider is registered in application', function (): void {
    expect(app()->getProviders(BroadcastServiceProvider::class))->not->toBeEmpty();
});
