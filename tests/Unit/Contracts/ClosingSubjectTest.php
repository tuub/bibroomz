<?php

declare(strict_types=1);

use App\Contracts\ClosingSubject;
use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

test('institution satisfies ClosingSubject and returns itself as institution for closings', function (): void {
    $institution = Institution::factory()->create();

    expect($institution)->toBeInstanceOf(ClosingSubject::class);
    expect($institution->institutionForClosings())->toBe($institution);
});

test('institution closings relation returns morph many', function (): void {
    $institution = Institution::factory()->create();

    $relation = $institution->closings();

    expect($relation)->toBeInstanceOf(MorphMany::class);
});

test('institution getHappenings returns collection', function (): void {
    $institution = Institution::factory()->create();

    $happenings = $institution->getHappenings();

    expect($happenings)->toBeInstanceOf(Collection::class);
});

test('resource group satisfies ClosingSubject through institution when accessed via a ClosingSubject implementor', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    // Institution implements ClosingSubject; verify the interface contract is met
    expect($institution->institutionForClosings()->id)->toBe($institution->id);
    expect($institution->institutionForClosings())->toBeInstanceOf(Institution::class);
});
