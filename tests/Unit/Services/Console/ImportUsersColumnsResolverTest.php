<?php

declare(strict_types=1);

use App\Services\Console\ImportUsersColumnsResolver;
use Illuminate\Validation\ValidationException;

covers(ImportUsersColumnsResolver::class);

test('resolver parses columns from option when provided', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "name,email\nAlice,alice@example.test\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,email',
        false
    );

    expect($columns)->toBe(['name', 'email']);
});

test('resolver skips csv header when header option is true and columns provided', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "header1,header2\nname,email\nAlice,alice@example.test\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,email',
        true
    );

    expect($columns)->toBe(['name', 'email']);
});

test('resolver reads csv header from file when no columns option provided', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "name,email\nAlice,alice@example.test\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        null,
        true
    );

    expect($columns)->toBe(['name', 'email']);
});

test('resolver validates columns contain required model keys', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, '');

    expect(fn (): array => $resolver->resolve(
        $file,
        ['name'],
        [],
        'email',  // Missing 'name'
        false
    ))->toThrow(ValidationException::class);
});

test('resolver filters empty strings from parsed columns', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, '');

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,,email,',
        false
    );

    expect($columns)->toBe(['name', 'email']);
});

test('resolver validates each column is a valid option', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, '');

    expect(fn (): array => $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,unknown_column',
        false
    ))->toThrow(ValidationException::class);
});

test('resolver includes relation keys as valid options', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "name,email,valid_from\n");
    rewind($file);

    // valid_from is a relation key — it must be accepted as a valid option
    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        ['valid_from'],
        null,
        true
    );

    expect($columns)->toBe(['name', 'email', 'valid_from']);
});

test('resolver filters null and empty entries from csv header', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    // A CSV header row with an empty last column
    fwrite($file, "name,email,\nAlice,alice@test.com,\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        null,
        true
    );

    expect($columns)->toBe(['name', 'email']);
});

// --- Mutation-killing tests ---

test('resolver validates columns contain required model keys via rule', function (): void {
    // 'contains:name,email' rule must be present; without it, missing keys would pass
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));

    expect(fn (): array => $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'email',  // 'name' missing from columns
        false
    ))->toThrow(ValidationException::class);

    fclose($file);
});

test('resolver validates each column is string and in options', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));

    expect(fn (): array => $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,email,invalid_column',
        false
    ))->toThrow(ValidationException::class);

    fclose($file);
});

test('resolver uses columnsOption when provided without header', function (): void {
    // When columnsOption is set, it should be used directly
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "Alice,alice@example.com\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,email',
        false
    );

    // File pointer should be at start (no fgetcsv called when no headerOption)
    expect($columns)->toBe(['name', 'email']);

    fclose($file);
});

test('resolver skips header line when columnsOption and headerOption both set', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    // First line is a header; second line is data
    fwrite($file, "col1,col2\nAlice,alice@example.com\n");
    rewind($file);

    // headerOption=true → fgetcsv($file) must be called to skip header
    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        'name,email',
        true
    );

    // After resolve, file pointer is past the header line
    // Columns from option should still be name,email
    expect($columns)->toBe(['name', 'email']);

    // Now reading should give us data line, not header
    $remaining = fgetcsv($file);
    expect($remaining)->toBe(['Alice', 'alice@example.com']);

    fclose($file);
});

test('resolver returns array_values from csv header', function (): void {
    $resolver = new ImportUsersColumnsResolver;
    $file = tmpfile();
    assert(is_resource($file));
    fwrite($file, "name,email\nAlice,alice@example.com\n");
    rewind($file);

    $columns = $resolver->resolve(
        $file,
        ['name', 'email'],
        [],
        null,
        true
    );

    // Keys must be sequential 0-based (array_values was applied)
    expect(array_keys($columns))->toBe([0, 1])
        ->and($columns)->toBe(['name', 'email']);

    fclose($file);
});
