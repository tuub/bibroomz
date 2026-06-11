<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Services\Console\CreateInstitutionAction;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

covers(CreateInstitutionAction::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

test('execute creates institution from validated input', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Test University', 'de' => 'Testuniversität'],
        'short_title' => 'TU',
        'slug' => 'test-university-'.uniqid(),
        'is_active' => true,
        'week_days' => [],
    ]);

    expect($institution)->toBeInstanceOf(Institution::class)
        ->and($institution->id)->not->toBeNull();
});

test('validateInput throws on missing required fields', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([]))->toThrow(ValidationException::class);
});

test('validateInput returns array with all provided keys', function (): void {
    $slug = 'my-inst-'.uniqid();
    $action = new CreateInstitutionAction;

    $result = $action->validateInput([
        'title' => ['en' => 'My Institution'],
        'short_title' => 'MI',
        'slug' => $slug,
        'is_active' => false,
    ]);

    expect($result)->toHaveKey('short_title')
        ->and($result)->toHaveKey('slug')
        ->and($result)->toHaveKey('is_active')
        ->and($result['slug'])->toBe($slug)
        ->and($result['short_title'])->toBe('MI')
        ->and($result['is_active'])->toBeFalse();
});

test('validateInput throws when slug is not unique', function (): void {
    $action = new CreateInstitutionAction;
    $slug = 'duplicate-slug-'.uniqid();

    $action->execute([
        'title' => ['en' => 'First'],
        'short_title' => 'F',
        'slug' => $slug,
        'is_active' => false,
    ]);

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Second'],
        'short_title' => 'S',
        'slug' => $slug,
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('execute syncs named week days as integers', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'WD Test'],
        'short_title' => 'WD',
        'slug' => 'wd-test-'.uniqid(),
        'is_active' => true,
        'week_days' => ['Monday', 'Wednesday', 'Friday'],
    ]);

    $institution->load('week_days');
    $ids = $institution->week_days->pluck('id')->sort()->values()->toArray();

    expect($ids)->toContain(1)
        ->and($ids)->toContain(3)
        ->and($ids)->toContain(5);
});

test('execute syncs numeric week days directly', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Numeric WD'],
        'short_title' => 'NWD',
        'slug' => 'numeric-wd-'.uniqid(),
        'is_active' => true,
        'week_days' => [2, 4],
    ]);

    $institution->load('week_days');
    $ids = $institution->week_days->pluck('id')->sort()->values()->toArray();

    expect($ids)->toContain(2)
        ->and($ids)->toContain(4);
});

test('execute skips null and array items in week_days', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Skip Test'],
        'short_title' => 'SK',
        'slug' => 'skip-wd-'.uniqid(),
        'is_active' => false,
        'week_days' => [null, [], 'Monday'],
    ]);

    $institution->load('week_days');
    $ids = $institution->week_days->pluck('id')->sort()->values()->toArray();

    expect($ids)->toContain(1)
        ->and(count($ids))->toBe(1);
});

test('execute creates institution settings from initial values', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Settings Test'],
        'short_title' => 'ST',
        'slug' => 'settings-test-'.uniqid(),
        'is_active' => false,
        'week_days' => [],
    ]);

    $institution->load('settings');

    expect($institution->settings)->not->toBeEmpty();
    $keys = $institution->settings->pluck('key')->toArray();
    expect($keys)->toContain('timezone')
        ->and($keys)->toContain('allowed_ips');
});

test('execute works when week_days key is absent', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'No WD'],
        'short_title' => 'NW',
        'slug' => 'no-wd-'.uniqid(),
        'is_active' => false,
    ]);

    expect($institution)->toBeInstanceOf(Institution::class);
});

test('validateInput accepts optional url fields', function (): void {
    $slug = 'url-test-'.uniqid();
    $action = new CreateInstitutionAction;

    $result = $action->validateInput([
        'title' => ['en' => 'URL Test'],
        'short_title' => 'UT',
        'slug' => $slug,
        'is_active' => false,
        'home_uri' => 'https://example.com',
        'logo_uri' => 'https://example.com/logo.png',
        'teaser_uri' => 'https://example.com/teaser.png',
    ]);

    expect($result)->toHaveKey('home_uri')
        ->and($result['home_uri'])->toBe('https://example.com');
});

test('validateInput rejects invalid email', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Email Test'],
        'short_title' => 'ET',
        'slug' => 'email-test-'.uniqid(),
        'is_active' => false,
        'email' => 'not-an-email',
    ]))->toThrow(ValidationException::class);
});

test('normalizeWeekDays maps all day names to integers', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Full WD'],
        'short_title' => 'FW',
        'slug' => 'full-wd-'.uniqid(),
        'is_active' => true,
        'week_days' => ['Tuesday', 'Thursday', 'Saturday', 'Sunday'],
    ]);

    $institution->load('week_days');
    $ids = $institution->week_days->pluck('id')->sort()->values()->toArray();

    expect($ids)->toContain(2)
        ->and($ids)->toContain(4)
        ->and($ids)->toContain(6)
        ->and($ids)->toContain(7);
});

// --- Mutation-killing tests for validateInput() rule array (lines 21-30 RemoveArrayItem) ---

test('validateInput requires title to have translations', function (): void {
    $action = new CreateInstitutionAction;

    // Empty title (no translations) should fail RequiredWithTranslationRule
    expect(fn (): array => $action->validateInput([
        'title' => [],
        'short_title' => 'X',
        'slug' => 'missing-title-'.uniqid(),
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput requires short_title', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Title'],
        'slug' => 'missing-short-'.uniqid(),
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput requires slug', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Title'],
        'short_title' => 'T',
        'is_active' => false,
    ]))->toThrow(ValidationException::class);
});

test('validateInput requires week_days when is_active is true', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Active Title'],
        'short_title' => 'AT',
        'slug' => 'active-wd-missing-'.uniqid(),
        'is_active' => true,
        // week_days intentionally omitted
    ]))->toThrow(ValidationException::class);
});

test('validateInput rejects invalid home_uri', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'URI Test'],
        'short_title' => 'UT',
        'slug' => 'home-uri-test-'.uniqid(),
        'is_active' => false,
        'home_uri' => 'not-a-url',
    ]))->toThrow(ValidationException::class);
});

test('validateInput rejects invalid logo_uri', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Logo Test'],
        'short_title' => 'LT',
        'slug' => 'logo-uri-test-'.uniqid(),
        'is_active' => false,
        'logo_uri' => 'not-a-url',
    ]))->toThrow(ValidationException::class);
});

test('validateInput rejects invalid teaser_uri', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Teaser Test'],
        'short_title' => 'TT',
        'slug' => 'teaser-uri-test-'.uniqid(),
        'is_active' => false,
        'teaser_uri' => 'not-a-url',
    ]))->toThrow(ValidationException::class);
});

test('validateInput requires is_active', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Active Required'],
        'short_title' => 'AR',
        'slug' => 'is-active-missing-'.uniqid(),
        // is_active intentionally omitted
    ]))->toThrow(ValidationException::class);
});

test('validateInput rejects non-boolean is_active', function (): void {
    $action = new CreateInstitutionAction;

    expect(fn (): array => $action->validateInput([
        'title' => ['en' => 'Bool Test'],
        'short_title' => 'BT',
        'slug' => 'bool-test-'.uniqid(),
        'is_active' => 'maybe',
    ]))->toThrow(ValidationException::class);
});

// --- Mutation-killing test for execute() settings creation ---

test('execute creates institution settings with key and value', function (): void {
    $action = new CreateInstitutionAction;
    $institution = $action->execute([
        'title' => ['en' => 'Settings Key Value Test'],
        'short_title' => 'SK',
        'slug' => 'settings-kv-'.uniqid(),
        'is_active' => false,
        'week_days' => [],
    ]);

    $institution->load('settings');

    expect($institution->settings)->not->toBeEmpty();
    $keys = $institution->settings->pluck('key')->toArray();
    expect($keys)->not->toBeEmpty()
        ->and(collect($keys)->filter(fn (mixed $k): bool => is_string($k) && $k !== '')->count())->toBeGreaterThan(0);
});
