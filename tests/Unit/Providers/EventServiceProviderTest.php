<?php

declare(strict_types=1);

use App\Providers\EventServiceProvider;

covers(EventServiceProvider::class);

test('event service provider does not auto-discover events', function (): void {
    $provider = new EventServiceProvider(app());

    expect($provider->shouldDiscoverEvents())->toBeFalse();
});
