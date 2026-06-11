<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Rules\UniqueResourceGroupAttributeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(UniqueResourceGroupAttributeRule::class);

uses(RefreshDatabase::class);

test('validation passes when slug is unique within institution', function (): void {
    $institution = Institution::factory()->create();

    $rule = new UniqueResourceGroupAttributeRule($institution->id, null);

    $failCalled = false;
    $rule->validate('slug', 'unique-slug', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});

test('validation fails when slug already exists in the same institution', function (): void {
    $institution = Institution::factory()->create();
    ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'taken-slug']);

    $rule = new UniqueResourceGroupAttributeRule($institution->id, null);

    $failCalled = false;
    $rule->validate('slug', 'taken-slug', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeTrue();
});

test('validation passes when slug belongs to the same resource group being updated', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'my-slug']);

    $rule = new UniqueResourceGroupAttributeRule($institution->id, $resourceGroup->id);

    $failCalled = false;
    $rule->validate('slug', 'my-slug', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});

test('validation fails when a different resource group in the same institution has the same slug', function (): void {
    $institution = Institution::factory()->create();
    ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'taken-slug']);
    $ownGroup = ResourceGroup::factory()->for($institution, 'institution')->create(['slug' => 'own-slug']);

    $rule = new UniqueResourceGroupAttributeRule($institution->id, $ownGroup->id);

    $failCalled = false;
    $rule->validate('slug', 'taken-slug', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeTrue();
});

test('slug collision in different institution does not fail', function (): void {
    $institutionA = Institution::factory()->create();
    $institutionB = Institution::factory()->create();
    ResourceGroup::factory()->for($institutionA, 'institution')->create(['slug' => 'shared-slug']);

    $rule = new UniqueResourceGroupAttributeRule($institutionB->id, null);

    $failCalled = false;
    $rule->validate('slug', 'shared-slug', function () use (&$failCalled): void {
        $failCalled = true;
    });

    expect($failCalled)->toBeFalse();
});
