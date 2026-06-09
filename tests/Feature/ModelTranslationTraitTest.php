<?php

use App\Models\Institution;
use App\Traits\HasTranslations;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(HasTranslations::class);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
    app()->setLocale('de');
});

afterEach(function (): void {
    app()->setLocale('de');
});

test('withoutTranslations returns locale-specific attribute values for the current locale', function (): void {
    $institution = Institution::factory()->create([
        'title' => ['de' => 'Bibliothek', 'en' => 'Library'],
    ]);

    app()->setLocale('de');
    $deData = $institution->withoutTranslations();
    expect($deData['title'])->toBe('Bibliothek');

    app()->setLocale('en');
    $enData = $institution->withoutTranslations();
    expect($enData['title'])->toBe('Library');
});

test('withTranslations returns the full translations array for all locales', function (): void {
    $institution = Institution::factory()->create([
        'title' => ['de' => 'Stadtbibliothek', 'en' => 'City Library'],
    ]);

    $data = $institution->withTranslations();

    expect($data['title'])->toBeArray()
        ->and($data['title']['de'])->toBe('Stadtbibliothek')
        ->and($data['title']['en'])->toBe('City Library');
});
