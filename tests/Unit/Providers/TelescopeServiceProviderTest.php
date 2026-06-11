<?php

declare(strict_types=1);

use App\Providers\TelescopeServiceProvider;

test('telescope service provider is registered in application', function (): void {
    expect(app()->getProviders(TelescopeServiceProvider::class))->not->toBeEmpty();
});
