<?php

use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PublicResourcesRequest;
use App\Http\Requests\ResourceGroupRouteRequest;
use App\Http\Requests\ResourceTimeSlotsRequest;
use App\Http\Requests\SwitchLanguageRequest;
use App\Http\Requests\UserHappeningsRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

covers(
    LoginRequest::class,
    PublicResourcesRequest::class,
    ResourceTimeSlotsRequest::class,
    SwitchLanguageRequest::class,
    UserHappeningsRequest::class,
    ResourceGroupRouteRequest::class
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(WeekDaySeeder::class);
});

test('login request requires both username and password', function (): void {
    $validator = Validator::make([], (new LoginRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKeys(['username', 'password']);
});

test('switch language request only accepts supported locales', function (): void {
    $validator = Validator::make(['locale' => 'fr'], (new SwitchLanguageRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKey('locale');
});

test('public resources request validates query fields and exposes typed helpers', function (): void {
    $request = buildFormRequest(PublicResourcesRequest::class, [
        'institution_slug' => 'central-library',
        'resource_group_slug' => 'rooms',
        'date' => '2026-06-03',
    ]);

    expect($request->authorize())->toBeTrue()
        ->and($request->perPage())->toBe(15)
        ->and($request->requestedDate()->format('Y-m-d'))->toBe('2026-06-03');

    $validator = Validator::make([
        'institution_slug' => 'central-library',
        'resource_group_slug' => 'rooms',
        'date' => '2026-06-03',
        'count' => 0,
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKey('count');
});

test('resource time slots request validates resource ids and parses timestamps', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create();

    $request = buildFormRequest(ResourceTimeSlotsRequest::class, [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => $resource->id,
        'start' => '2026-06-03 10:00:00',
        'end' => '2026-06-03 11:00:00',
    ]);

    $validator = Validator::make([
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'id' => $resource->id,
        'start' => '2026-06-03 10:00:00',
        'end' => '2026-06-03 11:00:00',
    ], $request->rules());

    expect($validator->fails())->toBeFalse()
        ->and($request->start()->format('Y-m-d H:i:s'))->toBe('2026-06-03 10:00:00')
        ->and($request->end()->format('Y-m-d H:i:s'))->toBe('2026-06-03 11:00:00');
});

test('user happenings request only authorizes authenticated users and validates the resource group id', function (): void {
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()
        ->for(Institution::factory()->create(['is_active' => true]), 'institution')
        ->create();

    $guestRequest = buildFormRequest(UserHappeningsRequest::class, ['resource_group_id' => $resourceGroup->id]);
    expect($guestRequest->authorize())->toBeFalse();

    $request = buildFormRequest(UserHappeningsRequest::class, ['resource_group_id' => $resourceGroup->id], $user);
    expect($request->authorize())->toBeTrue();

    $validator = Validator::make(['resource_group_id' => 'invalid'], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->messages())->toHaveKey('resource_group_id');
});

test('calendar entries request exposes start and end as CarbonImmutable instances', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    $request = buildRoutedFormRequest(
        CalendarEntriesRequest::class,
        'GET',
        sprintf('/%s/%s/happenings', $institution->slug, $resourceGroup->slug),
        [
            'start' => '2026-06-10 09:00:00',
            'end' => '2026-06-10 10:00:00',
        ],
    );

    $validator = Validator::make($request->validationData(), $request->rules());
    $request->setValidator($validator);

    expect($request->startAt())->toBeInstanceOf(CarbonImmutable::class)
        ->and($request->endAt())->toBeInstanceOf(CarbonImmutable::class);
});
