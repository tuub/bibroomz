<?php

declare(strict_types=1);

/**
 * @param  array<array-key, mixed>  $arr
 * @return array<string, mixed>
 */
function flattenLang(array $arr, string $prefix = ''): array
{
    /** @var array<string, mixed> $result */
    $result = [];
    foreach ($arr as $k => $v) {
        $key = $prefix !== '' ? "$prefix.$k" : (string) $k;
        if (is_array($v)) {
            /** @var array<array-key, mixed> $v */
            $result = array_merge($result, flattenLang($v, $key));
        } else {
            $result[$key] = $v;
        }
    }

    return $result;
}

dataset('locales', ['en', 'de']);

test('admin lang file has resource_groups_count in institutions table header', function (string $locale): void {
    /** @var array<array-key, mixed> $raw */
    $raw = include base_path("lang/$locale/admin.php");
    $flat = flattenLang($raw);

    expect($flat)->toHaveKey('institutions.index.table.header.resource_groups_count')
        ->and($flat['institutions.index.table.header.resource_groups_count'])->not->toBeEmpty();
})->with('locales');

test('admin lang file has resources_count in institutions table header', function (string $locale): void {
    /** @var array<array-key, mixed> $raw */
    $raw = include base_path("lang/$locale/admin.php");
    $flat = flattenLang($raw);

    expect($flat)->toHaveKey('institutions.index.table.header.resources_count')
        ->and($flat['institutions.index.table.header.resources_count'])->not->toBeEmpty();
})->with('locales');

test('login lang files have the same top-level keys in both locales', function (): void {
    /** @var array<array-key, mixed> $en */
    $en = include base_path('lang/en/login.php');
    /** @var array<array-key, mixed> $de */
    $de = include base_path('lang/de/login.php');

    $enKeys = array_keys(flattenLang($en));
    $deKeys = array_keys(flattenLang($de));

    $onlyInEn = array_diff($enKeys, $deKeys);
    $onlyInDe = array_diff($deKeys, $enKeys);

    expect($onlyInEn)->toBeEmpty('Keys only in EN login: '.implode(', ', $onlyInEn))
        ->and($onlyInDe)->toBeEmpty('Keys only in DE login: '.implode(', ', $onlyInDe));
});

test('login lang files have no empty string values', function (string $locale): void {
    /** @var array<array-key, mixed> $raw */
    $raw = include base_path("lang/$locale/login.php");
    $flat = flattenLang($raw);

    foreach ($flat as $key => $value) {
        expect($value)->not->toBeEmpty("Key '$key' is empty in $locale login");
    }
})->with('locales');

test('admin lang files have no empty string values for keys used in Vue templates', function (string $locale): void {
    /** @var array<array-key, mixed> $raw */
    $raw = include base_path("lang/$locale/admin.php");
    $flat = flattenLang($raw);

    $usedKeys = [
        'institutions.index.table.header.resource_groups_count',
        'institutions.index.table.header.resources_count',
        'institutions.index.table.header.is_active',
        'resource_groups.index.table.header.description',
        'resource_groups.index.table.header.is_active',
    ];

    foreach ($usedKeys as $key) {
        expect(isset($flat[$key]))->toBeTrue("Missing key '$key' in $locale")
            ->and($flat[$key] ?? '')->not->toBeEmpty("Key '$key' is empty in $locale");
    }
})->with('locales');
