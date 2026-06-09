<?php

use App\Http\Requests\AddHappeningRequest;
use App\Http\Requests\CalendarEntriesRequest;
use App\Http\Requests\DeleteHappeningRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PublicResourcesRequest;
use App\Http\Requests\ResourceGroupRouteRequest;
use App\Http\Requests\ResourceTimeSlotsRequest;
use App\Http\Requests\SwitchLanguageRequest;
use App\Http\Requests\UpdateHappeningRequest;
use App\Http\Requests\UserHappeningsRequest;
use App\Http\Requests\VerifyHappeningRequest;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

covers(
    AddHappeningRequest::class,
    UpdateHappeningRequest::class,
    VerifyHappeningRequest::class,
    DeleteHappeningRequest::class,
    CalendarEntriesRequest::class,
    LoginRequest::class,
    PublicResourcesRequest::class,
    ResourceTimeSlotsRequest::class,
    SwitchLanguageRequest::class,
    UserHappeningsRequest::class,
    ResourceGroupRouteRequest::class
);

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(WeekDaySeeder::class));

/**
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 */
function makePublicRules(string $class, array $input, ?User $user = null): array
{
    $request = buildFormRequest($class, $input, $user);

    /** @var array<string, mixed> */
    return $request->rules();
}

/**
 * @param  array<string, mixed>  $rules
 * @param  array<string, mixed>  $data
 */
function assertPublicFails(array $rules, array $data, string $field): void
{
    $v = Validator::make($data, $rules);
    expect($v->fails())->toBeTrue("Validation should fail for '{$field}'")
        ->and($v->errors()->has($field))->toBeTrue("Expected error on '{$field}'");
}

// ── UpdateHappeningRequest ────────────────────────────────────────────────────

test('update happening request requires id', function (): void {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, ['start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('update happening request rejects non-uuid id', function (): void {
    $rules = makePublicRules(UpdateHappeningRequest::class, ['id' => 'bad']);
    assertPublicFails($rules, ['id' => 'bad', 'start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('update happening request requires start', function (): void {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) Str::uuid(), 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

test('update happening request requires end', function (): void {
    $rules = makePublicRules(UpdateHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) Str::uuid(), 'start' => '2026-06-10 09:00:00',
    ], 'end');
});

test('update happening request rejects non-date start', function (): void {
    $rules = makePublicRules(UpdateHappeningRequest::class, ['start' => 'not-a-date']);
    assertPublicFails($rules, [
        'id' => (string) Str::uuid(), 'start' => 'not-a-date', 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

// ── VerifyHappeningRequest ────────────────────────────────────────────────────

test('verify happening request requires id', function (): void {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, ['start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('verify happening request rejects non-uuid id', function (): void {
    $rules = makePublicRules(VerifyHappeningRequest::class, ['id' => 'bad']);
    assertPublicFails($rules, ['id' => 'bad', 'start' => '2026-06-10 09:00:00', 'end' => '2026-06-10 10:00:00'], 'id');
});

test('verify happening request requires start', function (): void {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) Str::uuid(), 'end' => '2026-06-10 10:00:00',
    ], 'start');
});

test('verify happening request requires end', function (): void {
    $rules = makePublicRules(VerifyHappeningRequest::class, []);
    assertPublicFails($rules, [
        'id' => (string) Str::uuid(), 'start' => '2026-06-10 09:00:00',
    ], 'end');
});

// ── CalendarEntriesRequest ────────────────────────────────────────────────────

test('calendar entries request requires start', function (): void {
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

test('calendar entries request requires end', function (): void {
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

test('login request requires username', function (): void {
    $rules = makePublicRules(LoginRequest::class, []);
    assertPublicFails($rules, ['password' => 'secret'], 'username');
});

test('login request requires password', function (): void {
    $rules = makePublicRules(LoginRequest::class, []);
    assertPublicFails($rules, ['username' => 'alice'], 'password');
});

test('login request rejects empty username', function (): void {
    $rules = makePublicRules(LoginRequest::class, ['username' => '']);
    assertPublicFails($rules, ['username' => '', 'password' => 'secret'], 'username');
});

// ── SwitchLanguageRequest ─────────────────────────────────────────────────────

test('switch language request requires locale', function (): void {
    $rules = makePublicRules(SwitchLanguageRequest::class, []);
    assertPublicFails($rules, [], 'locale');
});

test('switch language request rejects unlisted locale', function (): void {
    $rules = makePublicRules(SwitchLanguageRequest::class, ['locale' => 'zz']);
    assertPublicFails($rules, ['locale' => 'zz'], 'locale');
});

// ── ResourceTimeSlotsRequest ──────────────────────────────────────────────────

test('resource time slots request requires resource_id', function (): void {
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

test('public resources request requires institution_slug', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $rules = makePublicRules(PublicResourcesRequest::class, [
        'resource_group_slug' => $resourceGroup->slug,
    ]);
    assertPublicFails($rules, ['resource_group_slug' => $resourceGroup->slug], 'institution_slug');
});

// ── UserHappeningsRequest ─────────────────────────────────────────────────────

test('user happenings request requires resource_group_id', function (): void {
    $rules = makePublicRules(UserHappeningsRequest::class, []);
    assertPublicFails($rules, [], 'resource_group_id');
});

// ── ResourceGroupRouteRequest ─────────────────────────────────────────────────

test('resource group route request requires institution_slug', function (): void {
    $rules = makePublicRules(ResourceGroupRouteRequest::class, []);
    assertPublicFails($rules, ['resource_group_slug' => 'rooms'], 'institution_slug');
});

test('resource group route request requires resource_group_slug', function (): void {
    $rules = makePublicRules(ResourceGroupRouteRequest::class, []);
    assertPublicFails($rules, ['institution_slug' => 'lib'], 'resource_group_slug');
});
