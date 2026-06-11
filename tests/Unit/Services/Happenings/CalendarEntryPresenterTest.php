<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Services\Happenings\CalendarEntryPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(CalendarEntryPresenter::class);

uses(RefreshDatabase::class);

test('presentHappening returns array with start and end keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentHappening($happening, null);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end');
});

test('presentHappening returns all required keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentHappening($happening, null);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('status')
        ->and($result)->toHaveKey('resourceId')
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end')
        ->and($result)->toHaveKey('classNames')
        ->and($result)->toHaveKey('can')
        ->and($result)->toHaveKey('isVerificationRequired')
        ->and($result)->toHaveKey('resource')
        ->and($result)->toHaveKey('label');
});

test('presentHappening resource subkey has resourceGroup and institution', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentHappening($happening, null);

    expect($result['resource'])->toHaveKey('resourceGroup')
        ->and($result['resource'])->toHaveKey('institution');
});

test('presentHappening resourceId matches the happening resource id', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();
    $happening = Happening::factory()->for($resource, 'resource')->create(['user_id_01' => $user->id]);

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentHappening($happening, null);

    expect($result['resourceId'])->toBe($resource->id)
        ->and($result['id'])->toBe($happening->id);
});

test('presentHappening formats start and end as Y-m-d H:i', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $user = User::factory()->create();

    $start = now()->addHour()->setSecond(0)->setMicro(0);
    $end = now()->addHours(2)->setSecond(0)->setMicro(0);

    $happening = Happening::factory()->for($resource, 'resource')->create([
        'user_id_01' => $user->id,
        'start' => $start,
        'end' => $end,
    ]);

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentHappening($happening, null);

    expect($result['start'])->toBe($start->format('Y-m-d H:i'))
        ->and($result['end'])->toBe($end->format('Y-m-d H:i'));
});

test('presentInstitutionClosing returns all required keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentInstitutionClosing($closing, $resource);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('status')
        ->and($result)->toHaveKey('resourceId')
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end')
        ->and($result)->toHaveKey('description')
        ->and($result)->toHaveKey('resource_group')
        ->and($result)->toHaveKey('user')
        ->and($result)->toHaveKey('classNames')
        ->and($result)->toHaveKey('display');
});

test('presentInstitutionClosing has correct static values', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentInstitutionClosing($closing, $resource);

    expect($result['status'])->toBeNull()
        ->and($result['user'])->toBeNull()
        ->and($result['classNames'])->toBe('closed')
        ->and($result['display'])->toBe('background');
});

test('presentInstitutionClosing resourceId matches the resource id', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($institution, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentInstitutionClosing($closing, $resource);

    expect($result['resourceId'])->toBe($resource->id)
        ->and($result['id'])->toBe($closing->id);
});

test('presentResourceClosing returns all required keys', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($resource, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentResourceClosing($closing, $resource);

    expect($result)->toHaveKey('id')
        ->and($result)->toHaveKey('status')
        ->and($result)->toHaveKey('resourceId')
        ->and($result)->toHaveKey('start')
        ->and($result)->toHaveKey('end')
        ->and($result)->toHaveKey('description')
        ->and($result)->toHaveKey('user')
        ->and($result)->toHaveKey('classNames')
        ->and($result)->toHaveKey('display');
});

test('presentResourceClosing has correct static values', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($resource, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentResourceClosing($closing, $resource);

    expect($result['status'])->toBeNull()
        ->and($result['user'])->toBeNull()
        ->and($result['classNames'])->toBe('closed')
        ->and($result['display'])->toBe('background');
});

test('presentResourceClosing does not have resource_group key', function (): void {
    $institution = Institution::factory()->create();
    $rg = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($rg, 'resource_group')->create();
    $closing = Closing::factory()->for($resource, 'closable')->create();

    $presenter = app(CalendarEntryPresenter::class);
    $result = $presenter->presentResourceClosing($closing, $resource);

    // presentResourceClosing does NOT include resource_group (unlike presentInstitutionClosing)
    expect($result)->not->toHaveKey('resource_group');
});
