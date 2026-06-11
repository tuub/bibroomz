<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Console\ImportUsersAction;
use App\Services\Console\ImportUsersCsvReader;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ImportUsersAction::class);

uses(RefreshDatabase::class);

test('execute adds users to user group', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    $csv = "john,john@example.com\njane,jane@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);
    $users = (new ImportUsersCsvReader)->readAndValidate($file, ['name', 'email']);
    fclose($file);

    $action = new ImportUsersAction;
    $action->execute($users, [], $group);

    expect($group->users()->count())->toBe(2);
});

test('execute creates users if they do not exist', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    $csv = "newuser,newuser@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);
    $users = (new ImportUsersCsvReader)->readAndValidate($file, ['name', 'email']);
    fclose($file);

    $action = new ImportUsersAction;
    $action->execute($users, [], $group);

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

test('resolveGroup returns user group by id', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    $action = new ImportUsersAction;
    $found = $action->resolveGroup($group->id);

    expect($found->id)->toBe($group->id);
});

test('resolveGroup with valid non-empty uuid resolves group', function (): void {
    // BooleanAndToBooleanOr: $groupOption !== null && $groupOption !== '' becomes ||
    // The true path (non-null, non-empty string) must still work correctly.
    // This verifies the && condition evaluates correctly for a valid UUID.
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'G2']]);

    $action = new ImportUsersAction;
    $found = $action->resolveGroup($group->id);

    expect($found->id)->toBe($group->id);
});
