<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Institution;
use App\Services\Closings\ClosingInstitutionResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(ClosingInstitutionResolver::class);

uses(RefreshDatabase::class);

test('resolveForClosing returns the institution for an institution closing', function (): void {
    $institution = Institution::factory()->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $resolver = new ClosingInstitutionResolver;
    $result = $resolver->resolveForClosing($closing);

    expect($result)->toBeInstanceOf(Institution::class)
        ->and($result->id)->toBe($institution->id);
});

test('resolveForClosable returns institution for institution closable', function (): void {
    $institution = Institution::factory()->create();

    $resolver = new ClosingInstitutionResolver;
    $result = $resolver->resolveForClosable($institution);

    expect($result->id)->toBe($institution->id);
});
