<?php

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command;

uses(RefreshDatabase::class);

function writeTempImportCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'roomz-ig-');
    file_put_contents($path, $contents);

    return $path;
}

afterEach(function (): void {
    foreach (glob(sys_get_temp_dir().'/roomz-ig-*') ?: [] as $path) {
        @unlink($path);
    }
});

test('import users command supports interactive group selection when no group option is given', function (): void {
    $institution = Institution::factory()->create(['title' => ['de' => 'Bibliothek', 'en' => 'Library']]);
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['de' => 'Gruppe', 'en' => 'Group']]);
    $path = writeTempImportCsv("name,email\nBob,bob@example.test\n");

    $displayLabel = sprintf('Group (%s)', 'Library');
    $groupId = (string) $group->id;

    $this->artisan('roomz:import-users', [
        'path' => $path,
        '--header' => 1,
    ])
        ->expectsChoice(
            'Select a user group to add the users to:',
            $groupId,
            [$displayLabel, $groupId],
        )
        ->assertExitCode(Command::SUCCESS);

    $user = User::where('email', 'bob@example.test')->firstOrFail();
    expect($user)->not->toBeNull()
        ->and($group->users()->where('users.id', $user->id)->exists())->toBeTrue();
});
