<?php

covers(App\Console\Kernel::class);

use App\Console\Commands\AnonymizeHappeningUsersCommand;
use App\Console\Commands\RemoveUnverifiedHappeningsCommand;
use App\Console\Commands\RemoveUsersCommand;
use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;

test('console kernel keeps the expected schedule for cleanup and prune commands', function () {
    $kernel = app(Kernel::class);
    $schedule = new Schedule(config('app.timezone'));

    $method = new ReflectionMethod($kernel, 'schedule');
    $method->setAccessible(true);
    $method->invoke($kernel, $schedule);

    $events = collect($schedule->events());
    $commands = $events->pluck('command')->join("\n");

    expect($commands)->toContain('roomz:remove-unverified-happenings')
        ->and($commands)->toContain('--force')
        ->and($commands)->toContain('--isolated')
        ->and($commands)->toContain('roomz:remove-users')
        ->and($commands)->toContain('roomz:anonymize-happening-users')
        ->and($commands)->toContain('model:prune')
        ->and($commands)->toContain('telescope:prune')
        ->and($commands)->toContain('ban:delete-expired');

    expect($events)->toHaveCount(6)
        ->and($events[0]->expression)->toBe('* * * * *')
        ->and($events[1]->expression)->toBe('5 4 * * *')
        ->and($events[2]->expression)->toBe('15 4 * * *')
        ->and($events[3]->expression)->toBe('35 4 * * *')
        ->and($events[4]->expression)->toBe('45 4 * * *')
        ->and($events[5]->expression)->toBe('* * * * *');
});
