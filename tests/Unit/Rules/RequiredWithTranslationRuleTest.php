<?php

declare(strict_types=1);

use App\Rules\RequiredWithTranslationRule;
use Illuminate\Support\Facades\Validator;

covers(RequiredWithTranslationRule::class);

beforeEach(function (): void {
    config(['app.supported_locales' => ['en', 'de']]);
});

test('validation passes when at least one supported locale has a value', function (): void {
    $validator = Validator::make(
        ['field' => ['en' => 'Hello', 'de' => '']],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeFalse();
});

test('validation passes when only the second locale has a value', function (): void {
    $validator = Validator::make(
        ['field' => ['en' => '', 'de' => 'Hallo']],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeFalse();
});

test('validation fails when all supported locales are empty', function (): void {
    $validator = Validator::make(
        ['field' => ['en' => '', 'de' => '']],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('validation fails when all locale values are null', function (): void {
    $validator = Validator::make(
        ['field' => ['en' => null, 'de' => null]],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('validation fails when value is not an array', function (): void {
    $validator = Validator::make(
        ['field' => 'just a string'],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('validation fails when value is null', function (): void {
    $validator = Validator::make(
        ['field' => null],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('validation fails when supported_locales config is not an array', function (): void {
    config(['app.supported_locales' => 'en']);

    $validator = Validator::make(
        ['field' => ['en' => 'Hello']],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeTrue();
});

test('validation passes when value has extra locales beyond supported ones', function (): void {
    $validator = Validator::make(
        ['field' => ['en' => 'Hello', 'de' => '', 'fr' => 'Bonjour']],
        ['field' => new RequiredWithTranslationRule]
    );

    expect($validator->fails())->toBeFalse();
});
