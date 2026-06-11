<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\UserGroup;
use App\Services\Console\CreateUserGroupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

covers(CreateUserGroupAction::class);

uses(RefreshDatabase::class);

test('execute creates user group', function (): void {
    $institution = Institution::factory()->create();

    $action = new CreateUserGroupAction;
    $ug = $action->execute([
        'title' => ['en' => 'Students', 'de' => 'Studierende'],
        'institution_id' => $institution->id,
    ]);

    expect($ug)->toBeInstanceOf(UserGroup::class)
        ->and($ug->institution_id)->toBe($institution->id);
});

test('validateInput throws on missing required fields', function (): void {
    $action = new CreateUserGroupAction;

    expect(fn (): array => $action->validateInput([]))->toThrow(ValidationException::class);
});
