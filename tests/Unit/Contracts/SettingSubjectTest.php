<?php

declare(strict_types=1);

use App\Contracts\SettingSubject;
use App\Models\Institution;
use App\Models\ResourceGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('institution satisfies SettingSubject and returns itself as institution for settings', function (): void {
    $institution = Institution::factory()->create();

    expect($institution)->toBeInstanceOf(SettingSubject::class);
    expect($institution->institutionForSettings())->toBe($institution);
});

test('resource group satisfies SettingSubject and returns its parent institution', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($resourceGroup)->toBeInstanceOf(SettingSubject::class);
    expect($resourceGroup->institutionForSettings()->id)->toBe($institution->id);
});
