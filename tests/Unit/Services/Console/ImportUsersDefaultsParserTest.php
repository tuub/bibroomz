<?php

declare(strict_types=1);

use App\Services\Console\ImportUsersDefaultsParser;

covers(ImportUsersDefaultsParser::class);

test('parse returns valid_from when from date given', function (): void {
    $parser = new ImportUsersDefaultsParser;
    $result = $parser->parse('2026-01-15', null);

    expect($result)->toHaveKey('valid_from')
        ->and($result['valid_from'])->toBe('2026-01-15');
});

test('parse returns valid_until when until date given', function (): void {
    $parser = new ImportUsersDefaultsParser;
    $result = $parser->parse(null, '2026-12-31');

    expect($result)->toHaveKey('valid_until')
        ->and($result['valid_until'])->toBe('2026-12-31');
});

test('parse returns both dates when both given', function (): void {
    $parser = new ImportUsersDefaultsParser;
    $result = $parser->parse('2026-01-01', '2026-12-31');

    expect($result)->toHaveKey('valid_from')
        ->and($result)->toHaveKey('valid_until');
});

test('parse returns empty array when no dates given', function (): void {
    $parser = new ImportUsersDefaultsParser;
    $result = $parser->parse(null, null);

    expect($result)->toBe([]);
});
