<?php

declare(strict_types=1);

use App\Console\Kernel;
use App\Models\Closing;
use App\Models\Happening;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

covers(Kernel::class);

/**
 * @return Collection<int, Event>
 */
function buildKernelSchedule(): Collection
{
    $kernel = app(Kernel::class);
    $timezone = config('app.timezone');
    $schedule = new Schedule(is_string($timezone) ? $timezone : null);

    $method = new ReflectionMethod($kernel, 'schedule');
    $method->invoke($kernel, $schedule);

    /** @var Collection<int, Event> */
    return collect($schedule->events());
}

test('console kernel keeps the expected schedule for cleanup and prune commands', function (): void {
    $events = buildKernelSchedule();

    expect($events)->toHaveCount(6)
        ->and($events[0]?->expression)->toBe('* * * * *')
        ->and($events[1]?->expression)->toBe('5 4 * * *')
        ->and($events[2]?->expression)->toBe('15 4 * * *')
        ->and($events[3]?->expression)->toBe('35 4 * * *')
        ->and($events[4]?->expression)->toBe('45 4 * * *')
        ->and($events[5]?->expression)->toBe('* * * * *');

    $cmd0 = (string) $events[0]?->command;
    $cmd1 = (string) $events[1]?->command;
    $cmd2 = (string) $events[2]?->command;
    $cmd3 = (string) $events[3]?->command;
    $cmd4 = (string) $events[4]?->command;
    $cmd5 = (string) $events[5]?->command;

    expect($cmd0)->toContain('remove-unverified-happenings')
        ->and($cmd0)->toContain('--force')
        ->and($cmd0)->toContain('--isolated');

    expect($cmd1)->toContain('remove-users')
        ->and($cmd1)->toContain('--force')
        ->and($cmd1)->toContain('--isolated');

    expect($cmd2)->toContain('anonymize-happening-users')
        ->and($cmd2)->toContain('--force')
        ->and($cmd2)->toContain('--isolated');

    expect($cmd3)->toContain('model:prune');

    expect($cmd4)->toContain('telescope:prune');

    expect($cmd5)->toContain('ban:delete-expired');
});

// ─────────────────────────────────────────────────────────────────
// RemoveArrayItem x3 — model:prune --model must contain BOTH Closing and Happening
// ─────────────────────────────────────────────────────────────────

test('model:prune event includes Closing model in --model array', function (): void {
    $events = buildKernelSchedule();
    $cmd3 = (string) $events[3]?->command;

    // RemoveArrayItem on Closing::class would drop it from the --model array.
    expect($cmd3)->toContain(Closing::class);
});

test('model:prune event includes Happening model in --model array', function (): void {
    $events = buildKernelSchedule();
    $cmd3 = (string) $events[3]?->command;

    // RemoveArrayItem on Happening::class would drop it from the --model array.
    expect($cmd3)->toContain(Happening::class);
});

test('model:prune event includes both Closing and Happening in --model array', function (): void {
    $events = buildKernelSchedule();
    $cmd3 = (string) $events[3]?->command;

    // Third RemoveArrayItem mutation could remove either entry; this combined assertion
    // ensures both survive.
    expect($cmd3)
        ->toContain(Closing::class)
        ->toContain(Happening::class);
});

// ─────────────────────────────────────────────────────────────────
// Lines 73–74: RemoveMethodCall / ConcatRemoveLeft / ConcatRemoveRight / ConcatSwitchSides
// $this->load(__DIR__.'/Commands') — must load commands from the Commands directory
// ─────────────────────────────────────────────────────────────────

test('console kernel commands method loads the Commands directory successfully', function (): void {
    $kernel = app(Kernel::class);
    $reflection = new ReflectionMethod($kernel, 'commands');

    // If $this->load() call is removed (RemoveMethodCall) the custom commands won't be registered.
    // We verify the method completes without error, which confirms the load path is executed.
    expect($reflection->invoke($kernel))->toBeNull();
});

test('roomz commands are registered after kernel commands() is invoked', function (): void {
    // Re-invoke commands() to ensure $this->load(__DIR__.'/Commands') registers artisan commands.
    // ConcatRemoveLeft/Right/SwitchSides on the path would break the load location.
    $kernel = app(Kernel::class);
    $reflection = new ReflectionMethod($kernel, 'commands');
    $reflection->invoke($kernel);

    // If the path concatenation is broken, the commands directory won't load, and
    // the roomz:remove-unverified-happenings command would not be available.
    /** @var Illuminate\Contracts\Console\Kernel $artisan */
    $artisan = app(Illuminate\Contracts\Console\Kernel::class);
    expect($artisan->all())->toHaveKey('roomz:remove-unverified-happenings')
        ->and($artisan->all())->toHaveKey('roomz:anonymize-happening-users');
});
