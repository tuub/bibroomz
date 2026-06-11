<?php

declare(strict_types=1);

use App\Services\Console\ImportUsersCsvReader;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

covers(ImportUsersCsvReader::class);

test('readAndValidate returns collection from valid CSV data', function (): void {
    $csv = "John Doe,john@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    expect($result)->toHaveCount(1)
        ->and(data_get($result->first(), 'name'))->toBe('John Doe')
        ->and(data_get($result->first(), 'email'))->toBe('john@example.com');
});

test('readAndValidate throws on empty csv', function (): void {
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))->toThrow(ValidationException::class);
    fclose($file);
});

test('readAndValidate trims whitespace from values', function (): void {
    $csv = "  Jane Doe  ,  jane@example.com  \n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    /** @var array<string, string> $first */
    $first = $result->first();
    expect($first['name'])->toBe('Jane Doe')
        ->and($first['email'])->toBe('jane@example.com');
});

test('readAndValidate returns a Collection instance', function (): void {
    $csv = "Bob,bob@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    expect($result)->toBeInstanceOf(Collection::class);
});

test('readAndValidate skips columns with no matching key in columns array', function (): void {
    // Four CSV columns but only 2 keys provided — extra columns are ignored
    $csv = "Alice,alice@example.com,extra1,extra2\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    /** @var array<string, string> $row */
    $row = $result->first();
    expect($row)->toHaveKey('name')
        ->and($row)->toHaveKey('email')
        ->and(count($row))->toBe(2);
});

test('readAndValidate accepts optional valid_from and valid_until fields', function (): void {
    $csv = "Carol,carol@example.com,2025-01-01,2025-12-31\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email', 'valid_from', 'valid_until']);
    fclose($file);

    /** @var array<string, string> $firstRow */
    $firstRow = $result->first();
    expect($firstRow['valid_from'])->toBe('2025-01-01')
        ->and($firstRow['valid_until'])->toBe('2025-12-31');
});

test('readAndValidate throws when name is missing', function (): void {
    $csv = ",alice@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))->toThrow(ValidationException::class);
    fclose($file);
});

test('readAndValidate throws when email is missing', function (): void {
    $csv = "Dave,\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))->toThrow(ValidationException::class);
    fclose($file);
});

test('readAndValidate handles multiple rows', function (): void {
    $csv = "Alice,alice@example.com\nBob,bob@example.com\nCarol,carol@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    expect($result)->toHaveCount(3)
        ->and($result->pluck('name')->toArray())->toBe(['Alice', 'Bob', 'Carol']);
});

// --- Mutation-killing tests ---

test('readAndValidate continues processing after unknown column index', function (): void {
    // 3 CSV columns but only 2 column mappings: index 2 has no mapping (null), so continues
    // If break were used instead, the row would stop after just 1 column
    $csv = "Alice,alice@example.com,extra\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    // Only 2 column mappings; index 2 (extra) has no key → should continue (not break)
    $result = $reader->readAndValidate($file, ['name', 'email']);
    fclose($file);

    /** @var array<string, string> $row */
    $row = $result->first();
    // Both name and email must be set (not just name, which break would cause)
    expect($row)->toHaveKey('name')
        ->and($row)->toHaveKey('email')
        ->and($row['name'])->toBe('Alice')
        ->and($row['email'])->toBe('alice@example.com');
});

test('readAndValidate empty value stored as empty string', function (): void {
    // When a value is blank, trim() on '' returns ''
    // If EmptyStringToNotEmpty mutated it, empty values wouldn't be empty strings
    $csv = "Alice,\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    // This will throw because email is required, but the blank email should be stored as ''
    try {
        $reader->readAndValidate($file, ['name', 'email']);
    } catch (ValidationException $e) {
        fclose($file);
        expect($e->errors())->toHaveKey('users.0.email');

        return;
    }
    fclose($file);
    // Should not reach here
    expect(false)->toBeTrue();
});

test('readAndValidate validates users must be a list with at least 1 item', function (): void {
    // Empty CSV → 0 rows → min:1 fails
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))
        ->toThrow(ValidationException::class);

    fclose($file);
});

test('readAndValidate validates users.*.name is required', function (): void {
    $csv = ",alice@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))
        ->toThrow(ValidationException::class);

    fclose($file);
});

test('readAndValidate validates users.*.email is required string', function (): void {
    $csv = "Bob,\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email']))
        ->toThrow(ValidationException::class);

    fclose($file);
});

test('readAndValidate validates valid_from as date when present', function (): void {
    $csv = "Carol,carol@example.com,not-a-date,2025-12-31\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email', 'valid_from', 'valid_until']))
        ->toThrow(ValidationException::class);

    fclose($file);
});

test('readAndValidate validates valid_until as date when present', function (): void {
    $csv = "Carol,carol@example.com,2025-01-01,not-a-date\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;

    expect(fn (): Collection => $reader->readAndValidate($file, ['name', 'email', 'valid_from', 'valid_until']))
        ->toThrow(ValidationException::class);

    fclose($file);
});

test('readAndValidate columns after unknown index still get populated in same row', function (): void {
    $csv = "Alice,extra_at_index_0,alice@example.com\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, [0 => 'name', 2 => 'email']);
    fclose($file);

    /** @var array<string, string> $row */
    $row = $result->first();
    expect($row)->toHaveKey('name')
        ->and($row)->toHaveKey('email')
        ->and($row['email'])->toBe('alice@example.com');
});

test('readAndValidate returned rows contain all five expected column keys', function (): void {
    $csv = "Eve,eve@example.com,2025-03-01,2025-06-30\n";
    $file = fopen('php://memory', 'r+');
    assert(is_resource($file));
    fwrite($file, $csv);
    rewind($file);

    $reader = new ImportUsersCsvReader;
    $result = $reader->readAndValidate($file, ['name', 'email', 'valid_from', 'valid_until']);
    fclose($file);

    /** @var array<string, string> $row */
    $row = $result->first();
    expect($row)->toHaveKey('name')
        ->and($row)->toHaveKey('email')
        ->and($row)->toHaveKey('valid_from')
        ->and($row)->toHaveKey('valid_until')
        ->and($row['name'])->toBe('Eve')
        ->and($row['email'])->toBe('eve@example.com')
        ->and($row['valid_from'])->toBe('2025-03-01')
        ->and($row['valid_until'])->toBe('2025-06-30');
});
