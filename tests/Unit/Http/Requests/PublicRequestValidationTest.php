<?php

covers(
    App\Http\Requests\AddHappeningRequest::class,
    App\Http\Requests\UpdateHappeningRequest::class,
    App\Http\Requests\VerifyHappeningRequest::class,
    App\Http\Requests\DeleteHappeningRequest::class,
    App\Http\Requests\CalendarEntriesRequest::class,
    App\Http\Requests\LoginRequest::class,
    App\Http\Requests\PublicResourcesRequest::class,
    App\Http\Requests\ResourceTimeSlotsRequest::class,
    App\Http\Requests\SwitchLanguageRequest::class,
    App\Http\Requests\UserHappeningsRequest::class,
    App\Http\Requests\ResourceGroupRouteRequest::class
);

use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PublicResourcesRequest;
use App\Http\Requests\ResourceGroupRouteRequest;
use App\Http\Requests\ResourceTimeSlotsRequest;
use App\Http\Requests\SwitchLanguageRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\UserHappeningsRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Models\Institution;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(WeekDaySeeder::class));

function makePublicRules(string $class, array $input, ?User $user = null): array
{
    $request = buildFormRequest($class, $input, $user);
    return $request->rules();
}

function assertPublicFails(array $rules, array $data, string $field): void
{
    $v = Validator::make($data, $rules);
    expect($v->fails())->toBeTrue("Validation should fail for '{$field}'")
        ->and($v->errors()->has($field))->toBeTrue("Expected error on '{$field}'");
}

// ── UpdateHappeningRequest ────────────────────────────────────────────────────

test('update happening request requires id', function () {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, ['start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('update happening request rejects non-uuid id', function () {
    $rules = makePublicRules(UpdateHappeningRequest::class, ['id' => 'bad']);
    assertPublicFails($rules, ['id' => 'bad', 'start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('update happening request requires start', function () {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(), 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

test('update happening request requires end', function () {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(), 'start' => '2026-06-10 09:00:00',
    ], 'end');
});

test('update happening request rejects non-date start', function () {
    $rules = makePublicRules(UpdateHappeningRequest::class, ['start' => 'not-a-date']);
    assertPublicFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(), 'start' => 'not-a-date', 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

// ── VerifyHappeningRequest ────────────────────────────────────────────────────

test('verify happening request requires id', function () {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, ['start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('verify happening request rejects non-uuid id', function () {
    $rules = makePublicRules(VerifyHappeningRequest::class, ['id' => 'bad']);
    assertPublicFails($rules, ['id' => 'bad', 'start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('verify happening request requires start', function () {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(), 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

test('verify happening request requires end', function () {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(), 'start' => '2026-06-10 09:00:00',
    ], 'end');
});

// ── CalendarEntriesRequest ────────────────────────────────────────────────────

test('calendar entries request requires start', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = makePublicRules(CalendarEntriesRequest::class, [
        'institution_slug' => $institution->slug, 'resource_group_slug' => $resourceGroup->slug,
    ]);
    assertPublicFails($rules, [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'end' => '2026-06-10 23:59:59',
    ], 'start');
});

test('calendar entries request requires end', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = makePublicRules(CalendarEntriesRequest::class, [
        'institution_slug' => $institution->slug, 'resource_group_slug' => $resourceGroup->slug,
    ]);
    assertPublicFails($rules, [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
        'start' => '2026-06-10 00:00:00',
    ], 'end');
});

// ── LoginRequest ──────────────────────────────────────────────────────────────

test('login request requires username', function () {
    $rules = makePublicRules(LoginRequest::class, []);
    assertPublicFails($rules, ['password' => 'secret'], 'username');
});

test('login request requires password', function () {
    $rules = makePublicRules(LoginRequest::class, []);
    assertPublicFails($rules, ['username' => 'alice'], 'password');
});

test('login request rejects empty username', function () {
    $rules = makePublicRules(LoginRequest::class, ['username' => '']);
    assertPublicFails($rules, ['username' => '', 'password' => 'secret'], 'username');
});

// ── SwitchLanguageRequest ─────────────────────────────────────────────────────

test('switch language request requires locale', function () {
    $rules = makePublicRules(SwitchLanguageRequest::class, []);
    assertPublicFails($rules, [], 'locale');
});

test('switch language request rejects unlisted locale', function () {
    $rules = makePublicRules(SwitchLanguageRequest::class, ['locale' => 'zz']);
    assertPublicFails($rules, ['locale' => 'zz'], 'locale');
});

// ── ResourceTimeSlotsRequest ──────────────────────────────────────────────────

test('resource time slots request requires resource_id', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = makePublicRules(ResourceTimeSlotsRequest::class, [
        'institution_slug' => $institution->slug,
        'resource_group_slug' => $resourceGroup->slug,
    ]);
    assertPublicFails($rules, [
        'institution_slug' => $institution->slug, 'resource_group_slug' => $resourceGroup->slug,
        'start' => '2026-06-10 00:00:00', 'end' => '2026-06-10 23:59:59',
    ], 'id');
});

// ── PublicResourcesRequest ────────────────────────────────────────────────────

test('public resources request requires institution_slug', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = makePublicRules(PublicResourcesRequest::class, [
        'resource_group_slug' => $resourceGroup->slug,
    ]);
    assertPublicFails($rules, ['resource_group_slug' => $resourceGroup->slug], 'institution_slug');
});

// ── UserHappeningsRequest ─────────────────────────────────────────────────────

test('user happenings request requires resource_group_id', function () {
    $rules = makePublicRules(UserHappeningsRequest::class, []);
    assertPublicFails($rules, [], 'resource_group_id');
});

// ── ResourceGroupRouteRequest ─────────────────────────────────────────────────

test('resource group route request requires institution_slug', function () {
    $rules = makePublicRules(ResourceGroupRouteRequest::class, []);
    assertPublicFails($rules, ['resource_group_slug' => 'rooms'], 'institution_slug');
});

test('resource group route request requires resource_group_slug', function () {
    $rules = makePublicRules(ResourceGroupRouteRequest::class, []);
    assertPublicFails($rules, ['institution_slug' => 'lib'], 'resource_group_slug');
});
