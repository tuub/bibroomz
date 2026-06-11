<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Services\Http\PublicResourcePresenter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(PublicResourcePresenter::class);

uses(RefreshDatabase::class);

test('present returns array with all expected top-level keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result)->toHaveKeys([
        'id',
        'title',
        'businessHours',
        'isVerificationRequired',
        'capacity',
        'location_uri',
        'resourceGroup',
        'order',
        'translations',
    ]);
});

test('present translations sub-array contains all expected keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['translations'])->toHaveKeys([
        'title',
        'description',
        'location',
        'resourceGroup',
    ]);
});

test('present id matches the resource id', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['id'])->toBe($resource->id);
});

test('present resourceGroup is the resource group id', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['resourceGroup'])->toBe($rg->id);
});

test('present businessHours entry has startTime endTime and daysOfWeek keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['businessHours'][0])->toHaveKeys(['startTime', 'endTime', 'daysOfWeek']);
});

test('present businessHours fallback entry is added when resource has no business hours for given date', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();

    // Use a past date far outside any business hours so the resolver returns empty
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    // Remove all business hours so none apply
    $resource->business_hours()->delete();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['businessHours'])->toHaveCount(1)
        ->and($result['businessHours'][0]['startTime'])->toBe('')
        ->and($result['businessHours'][0]['endTime'])->toBe('');
});

test('present isVerificationRequired reflects resource attribute', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create(['is_verification_required' => true]);

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['isVerificationRequired'])->toBeTrue();
});

test('present translations resourceGroup uses resource_group translations not resource', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create([
        'term_singular' => ['en' => 'Room', 'de' => 'Raum'],
    ]);
    $resource = Resource::factory()->for($rg, 'resource_group')->create();

    $presenter = app(PublicResourcePresenter::class);
    $result = $presenter->present($resource, $rg, CarbonImmutable::today());

    expect($result['translations']['resourceGroup'])->toBeArray()
        ->and($result['translations']['resourceGroup']['en'])->toBe('Room');
});
