<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Services\Console\CreateInstitutionResourceGroupAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

covers(CreateInstitutionResourceGroupAction::class);

uses(RefreshDatabase::class);

test('execute creates resource group from validated input', function (): void {
    $institution = Institution::factory()->create();

    $action = new CreateInstitutionResourceGroupAction;
    $rg = $action->execute([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Rooms', 'de' => 'Räume'],
        'slug' => 'rooms-'.uniqid(),
        'term_singular' => ['en' => 'Room'],
        'term_plural' => ['en' => 'Rooms'],
        'description' => ['en' => 'Room bookings'],
        'is_active' => true,
    ]);

    expect($rg)->toBeInstanceOf(ResourceGroup::class)
        ->and($rg->institution_id)->toBe($institution->id);
});

test('validateInput throws on missing required fields', function (): void {
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([]))->toThrow(ValidationException::class);
});

test('validateInput returns array with all provided keys', function (): void {
    $institution = Institution::factory()->create();
    $slug = 'rg-'.uniqid();
    $action = new CreateInstitutionResourceGroupAction;

    $result = $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Books'],
        'slug' => $slug,
        'term_singular' => ['en' => 'Book'],
        'term_plural' => ['en' => 'Books'],
        'description' => ['en' => 'Library'],
        'is_active' => true,
    ]);

    expect($result)->toHaveKey('institution_id')
        ->and($result)->toHaveKey('slug')
        ->and($result)->toHaveKey('is_active')
        ->and($result['slug'])->toBe($slug)
        ->and($result['institution_id'])->toBe($institution->id);
});

test('validateInput throws when institution does not exist', function (): void {
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => 'non-existent-id',
        'title' => ['en' => 'Test'],
        'slug' => 'test-slug',
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('execute creates settings for new resource group', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    $rg = $action->execute([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Labs'],
        'slug' => 'labs-'.uniqid(),
        'term_singular' => ['en' => 'Lab'],
        'term_plural' => ['en' => 'Labs'],
        'description' => ['en' => 'Laboratory spaces'],
        'is_active' => false,
    ]);

    $rg->load('settings');

    expect($rg->settings)->not->toBeEmpty();
    $keys = $rg->settings->pluck('key')->toArray();
    expect($keys)->toContain('start_time_slot')
        ->and($keys)->toContain('end_time_slot')
        ->and($keys)->toContain('time_slot_length');
});

test('execute returns ResourceGroup with correct institution relationship', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    $rg = $action->execute([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Studios'],
        'slug' => 'studios-'.uniqid(),
        'term_singular' => ['en' => 'Studio'],
        'term_plural' => ['en' => 'Studios'],
        'description' => ['en' => 'Art studios'],
        'is_active' => true,
    ]);

    expect($rg->institution_id)->toBe($institution->id)
        ->and($rg->id)->not->toBeNull();
});

test('validateInput throws when slug is missing', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'No Slug'],
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when title translations are empty', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => [],
        'slug' => 'no-title',
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when term_singular translations are empty', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Test'],
        'slug' => 'no-term-singular',
        'term_singular' => [],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when term_plural translations are empty', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Test'],
        'slug' => 'no-term-plural',
        'term_singular' => ['en' => 'Item'],
        'term_plural' => [],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when description translations are empty', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Test'],
        'slug' => 'no-description',
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => [],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when institution_id is absent and all other fields are valid', function (): void {
    // RemoveArrayItem on line 21 may remove 'required' from institution_id's rule list.
    // With only the other required fields present (no institution_id key at all), the 'required'
    // rule is the only thing that catches the omission; the 'exists' rule is silent on absent keys.
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Missing ID'],
        'slug' => 'missing-id-'.uniqid(),
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput throws when is_active is missing', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    expect(fn (): array => $action->validateInput([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Test'],
        'slug' => 'no-is-active',
        'term_singular' => ['en' => 'Item'],
        'term_plural' => ['en' => 'Items'],
        'description' => ['en' => 'Desc'],
    ]))->toThrow(ValidationException::class);
});

test('execute settings contain all expected keys including start and end time slots', function (): void {
    $institution = Institution::factory()->create();
    $action = new CreateInstitutionResourceGroupAction;

    $rg = $action->execute([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Keys Test'],
        'slug' => 'keys-test-'.uniqid(),
        'term_singular' => ['en' => 'Room'],
        'term_plural' => ['en' => 'Rooms'],
        'description' => ['en' => 'For key test'],
        'is_active' => true,
    ]);

    $rg->load('settings');
    $keys = $rg->settings->pluck('key')->toArray();

    // RemoveArrayItem would drop certain settings; verify critical ones exist
    expect($keys)->toContain('start_time_slot')
        ->and($keys)->toContain('end_time_slot')
        ->and($keys)->toContain('time_slot_length');
});
