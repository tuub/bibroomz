<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Services\Http\InstitutionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(InstitutionAccessService::class);

uses(RefreshDatabase::class);

test('isIpAllowed returns true when allowed_ips setting contains the ip', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'allowed_ips')->update(['value' => '127.0.0.1']);

    $service = app(InstitutionAccessService::class);

    expect($service->isIpAllowed($institution, '127.0.0.1'))->toBeTrue();
});

test('isIpAllowed returns false when ip is not in allowed_ips', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'allowed_ips')->update(['value' => '10.0.0.1']);

    $service = app(InstitutionAccessService::class);

    expect($service->isIpAllowed($institution, '192.168.1.1'))->toBeFalse();
});

test('isIpAllowed returns true when ip matches cidr range', function (): void {
    $institution = Institution::factory()->create();
    $institution->settings()->where('key', 'allowed_ips')->update(['value' => '192.168.0.0/24']);
    $institution->refresh();
    $institution->load('settings');

    $service = app(InstitutionAccessService::class);

    expect($service->isIpAllowed($institution, '192.168.0.50'))->toBeTrue();
});

test('filterAllowed removes institutions where ip is not allowed', function (): void {
    $allowedInstitution = Institution::factory()->create();
    $allowedInstitution->settings()->where('key', 'allowed_ips')->update(['value' => '10.0.0.1']);

    $blockedInstitution = Institution::factory()->create();
    $blockedInstitution->settings()->where('key', 'allowed_ips')->update(['value' => '10.0.0.2']);

    $institutions = collect([$allowedInstitution, $blockedInstitution]);

    $service = app(InstitutionAccessService::class);
    $filtered = $service->filterAllowed($institutions, '10.0.0.1');

    expect($filtered)->toHaveCount(1)
        ->and($filtered->first()?->id)->toBe($allowedInstitution->id);
});
