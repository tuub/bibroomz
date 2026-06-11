<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Traits\HasTranslations;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HasTranslations::class);

uses(RefreshDatabase::class);

test('withoutTranslations returns resolved translation for current locale', function (): void {
    app()->setLocale('en');

    $institution = Institution::factory()->create([
        'title' => ['en' => 'English Title', 'de' => 'Deutscher Titel'],
    ]);

    $result = $institution->withoutTranslations();

    expect($result)->toBeArray()
        ->and($result['title'])->toBe('English Title');
});

test('withoutTranslations resolves correct locale when locale is switched', function (): void {
    app()->setLocale('de');

    $institution = Institution::factory()->create([
        'title' => ['en' => 'English Title', 'de' => 'Deutscher Titel'],
    ]);

    $result = $institution->withoutTranslations();

    expect($result['title'])->toBe('Deutscher Titel');
});

test('withTranslations returns raw translation array for translatable fields', function (): void {
    $translations = ['en' => 'English Title', 'de' => 'Deutscher Titel'];

    $institution = Institution::factory()->create([
        'title' => $translations,
    ]);

    $result = $institution->withTranslations();

    expect($result)->toBeArray()
        ->and($result['title'])->toBe($translations);
});

test('withTranslations and withoutTranslations return arrays with string keys only', function (): void {
    $institution = Institution::factory()->create([
        'title' => ['en' => 'Title'],
    ]);

    $withTranslations = $institution->withTranslations();
    $withoutTranslations = $institution->withoutTranslations();

    foreach (array_keys($withTranslations) as $key) {
        expect($key)->toBeString();
    }

    foreach (array_keys($withoutTranslations) as $key) {
        expect($key)->toBeString();
    }
});
