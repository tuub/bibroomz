<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;

covers(AppServiceProvider::class);

test('app service provider is registered in application', function (): void {
    expect(app()->getProviders(AppServiceProvider::class))->not->toBeEmpty();
});
