<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UserGroup::class);

uses(RefreshDatabase::class);

test('user group can be created', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Students', 'de' => 'Studierende']]);

    expect($group->id)->not->toBeNull()
        ->and($group->getTranslation('title', 'en'))->toBe('Students');
});

test('user group has institution relationship', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    expect($group->institution()->firstOrFail()->id)->toBe($institution->id);
});

test('user group has users relationship', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    expect($group->users()->count())->toBe(0);
});

test('user group has resource_groups relationship', function (): void {
    $institution = Institution::factory()->create();
    $group = UserGroup::create(['institution_id' => $institution->id, 'title' => ['en' => 'Group']]);

    expect($group->resource_groups()->count())->toBe(0);
});
