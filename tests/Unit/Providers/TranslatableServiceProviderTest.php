<?php

declare(strict_types=1);

use App\Providers\TranslatableServiceProvider;

covers(TranslatableServiceProvider::class);

test('translatable service provider is registered in application', function (): void {
    expect(app()->getProviders(TranslatableServiceProvider::class))->not->toBeEmpty();
});
