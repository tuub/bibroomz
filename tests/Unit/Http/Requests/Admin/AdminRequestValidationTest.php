<?php

covers(
    App\Http\Requests\Admin\InstitutionRequest::class,
    App\Http\Requests\Admin\MailContentRequest::class,
    App\Http\Requests\Admin\ResourceGroupRequest::class,
    App\Http\Requests\Admin\StoreUserGroupRequest::class,
    App\Http\Requests\Admin\UpdateUserGroupRequest::class,
    App\Http\Requests\Admin\RoleRequest::class,
    App\Http\Requests\Admin\InstitutionOrderRequest::class,
    App\Http\Requests\Admin\ResourceGroupOrderRequest::class,
    App\Http\Requests\Admin\ResourceOrderRequest::class,
    App\Http\Requests\Admin\HappeningRequest::class,
    App\Http\Requests\Admin\StoreHappeningRequest::class,
    App\Http\Requests\Admin\UpdateHappeningRequest::class,
    App\Http\Requests\Admin\ResourceRequest::class,
    App\Http\Requests\Admin\StoreResourceRequest::class,
    App\Http\Requests\Admin\UpdateResourceRequest::class,
    App\Http\Requests\Admin\ImportUsersRequest::class,
    App\Http\Requests\Admin\RemoveUsersFromUserGroupRequest::class,
    App\Http\Requests\Admin\StoreClosingRequest::class,
    App\Http\Requests\Admin\UpdateClosingRequest::class,
    App\Http\Requests\Admin\PermissionGroupRequest::class,
    App\Http\Requests\Admin\PermissionRequest::class,
    App\Http\Requests\Admin\UserRequest::class
);

use App\Http\Requests\Admin\ImportUsersRequest;
use App\Http\Requests\Admin\InstitutionOrderRequest;
use App\Http\Requests\Admin\InstitutionRequest;
use App\Http\Requests\Admin\MailContentRequest;
use App\Http\Requests\Admin\PermissionGroupRequest;
use App\Http\Requests\Admin\PermissionRequest;
use App\Http\Requests\Admin\RemoveUsersFromUserGroupRequest;
use App\Http\Requests\Admin\ResourceGroupOrderRequest;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Http\Requests\Admin\ResourceOrderRequest;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\StoreHappeningRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\MailTypeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WeekDaySeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(MailTypeSeeder::class);
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function makeRules(string $class, array $input, ?User $user = null): array
{
    $request = buildFormRequest($class, $input, $user);
    return $request->rules();
}

function assertFails(array $rules, array $data, string $field): void
{
    $v = Validator::make($data, $rules);
    expect($v->fails())->toBeTrue("Expected validation to fail for field '{$field}'")
        ->and($v->errors()->has($field))->toBeTrue("Expected error on field '{$field}'");
}

function assertPasses(array $rules, array $data): void
{
    $v = Validator::make($data, $rules);
    expect($v->passes())->toBeTrue(
        'Unexpected validation failure: ' . json_encode($v->errors()->all())
    );
}

// ── InstitutionRequest ────────────────────────────────────────────────────────

test('institution request requires short_title', function () {
    $rules = makeRules(InstitutionRequest::class, [
        'title' => Utility::getTranslatable('Lib'),
        'is_active' => false,
    ]);
    assertFails($rules, ['title' => Utility::getTranslatable('Lib'), 'is_active' => false], 'short_title');
});

test('institution request requires slug', function () {
    $rules = makeRules(InstitutionRequest::class, ['is_active' => false]);
    assertFails($rules, [
        'title' => Utility::getTranslatable('Lib'), 'short_title' => 'L', 'is_active' => false,
    ], 'slug');
});

test('institution request requires is_active', function () {
    $rules = makeRules(InstitutionRequest::class, []);
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib', 'title' => Utility::getTranslatable('Lib'),
    ], 'is_active');
});

test('institution request rejects non-boolean is_active', function () {
    $rules = makeRules(InstitutionRequest::class, ['is_active' => 'yes']);
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => 'yes',
    ], 'is_active');
});

test('institution request rejects invalid email', function () {
    $rules = makeRules(InstitutionRequest::class, ['is_active' => false]);
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => false, 'email' => 'not-an-email',
    ], 'email');
});

test('institution request rejects invalid url fields', function () {
    $rules = makeRules(InstitutionRequest::class, ['is_active' => false]);
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => false, 'home_uri' => 'not-a-url',
    ], 'home_uri');
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => false, 'logo_uri' => 'not-a-url',
    ], 'logo_uri');
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => false, 'teaser_uri' => 'not-a-url',
    ], 'teaser_uri');
});

test('institution request requires week_days when is_active is true', function () {
    $rules = makeRules(InstitutionRequest::class, ['is_active' => true, 'week_days' => []]);
    assertFails($rules, [
        'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => true, 'week_days' => [],
    ], 'week_days');
});

test('institution request rejects non-uuid id', function () {
    $rules = makeRules(InstitutionRequest::class, ['id' => 'not-a-uuid', 'is_active' => false]);
    assertFails($rules, ['id' => 'not-a-uuid', 'short_title' => 'L', 'slug' => 'lib',
        'title' => Utility::getTranslatable('Lib'), 'is_active' => false], 'id');
});

// ── MailContentRequest ────────────────────────────────────────────────────────

test('mail content request requires institution_id', function () {
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, ['mail_type_id' => $mailType?->id]);
    assertFails($rules, ['mail_type_id' => $mailType?->id, 'subject' => 'Test', 'is_active' => true], 'institution_id');
});

test('mail content request rejects non-uuid institution_id', function () {
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, ['institution_id' => 'bad', 'mail_type_id' => $mailType?->id]);
    assertFails($rules, [
        'institution_id' => 'bad', 'mail_type_id' => $mailType?->id, 'subject' => 'Test', 'is_active' => true,
    ], 'institution_id');
});

test('mail content request requires mail_type_id', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(MailContentRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'subject' => 'Test', 'is_active' => true,
    ], 'mail_type_id');
});

test('mail content request requires subject', function () {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id, 'is_active' => true,
    ], 'subject');
});

test('mail content request requires is_active', function () {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id, 'subject' => 'Test',
    ], 'is_active');
});

test('mail content request requires action_uri_label when action_uri is present', function () {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
        'action_uri' => 'https://example.com',
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
        'subject' => 'Test', 'is_active' => true, 'action_uri' => 'https://example.com',
    ], 'action_uri_label');
});

// ── ResourceGroupRequest ──────────────────────────────────────────────────────

test('resource group request requires institution_id', function () {
    $rules = makeRules(ResourceGroupRequest::class, []);
    assertFails($rules, [
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms',
        'term_singular' => Utility::getTranslatable('Room'),
        'term_plural' => Utility::getTranslatable('Rooms'),
        'description' => Utility::getTranslatable('Desc'),
        'is_active' => false,
    ], 'institution_id');
});

test('resource group request rejects non-uuid institution_id', function () {
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => 'bad']);
    assertFails($rules, [
        'institution_id' => 'bad',
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms',
        'is_active' => false,
    ], 'institution_id');
});

test('resource group request requires slug', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'),
        'is_active' => false,
    ], 'slug');
});

test('resource group request requires is_active', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms',
    ], 'is_active');
});

test('resource group request rejects non-boolean is_active', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms', 'is_active' => 'yes',
    ], 'is_active');
});

test('resource group request rejects non-uuid user_groups items', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms', 'is_active' => false,
        'user_groups' => ['not-a-uuid'],
    ], 'user_groups.0');
});

test('resource group request rejects invalid help_uri', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, ['institution_id' => $institution->id]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms', 'is_active' => false,
        'help_uri' => 'not-a-url',
    ], 'help_uri');
});

// ── StoreUserGroupRequest / UpdateUserGroupRequest ────────────────────────────

test('store user group request requires institution_id', function () {
    $rules = makeRules(StoreUserGroupRequest::class, []);
    assertFails($rules, ['title' => Utility::getTranslatable('Group')], 'institution_id');
});

test('store user group request rejects non-uuid institution_id', function () {
    $rules = makeRules(StoreUserGroupRequest::class, ['institution_id' => 'bad']);
    assertFails($rules, ['institution_id' => 'bad', 'title' => Utility::getTranslatable('Group')], 'institution_id');
});

test('update user group request requires id', function () {
    $rules = makeRules(UpdateUserGroupRequest::class, []);
    assertFails($rules, ['title' => Utility::getTranslatable('Group')], 'id');
});

test('update user group request rejects non-uuid id', function () {
    $rules = makeRules(UpdateUserGroupRequest::class, ['id' => 'bad']);
    assertFails($rules, ['id' => 'bad', 'title' => Utility::getTranslatable('Group')], 'id');
});

// ── RoleRequest ───────────────────────────────────────────────────────────────

test('role request rejects non-array permissions', function () {
    $rules = makeRules(RoleRequest::class, ['permissions' => 'string-not-array']);
    assertFails($rules, ['name' => Utility::getTranslatable('Editor'), 'permissions' => 'bad'], 'permissions');
});

test('role request rejects non-uuid permissions items', function () {
    $rules = makeRules(RoleRequest::class, ['permissions' => ['not-a-uuid']]);
    assertFails($rules, [
        'name' => Utility::getTranslatable('Editor'), 'permissions' => ['not-a-uuid'],
    ], 'permissions.0');
});

test('role request rejects non-uuid id when provided', function () {
    $rules = makeRules(RoleRequest::class, ['id' => 'bad-id']);
    assertFails($rules, ['id' => 'bad-id', 'name' => Utility::getTranslatable('Editor')], 'id');
});

// ── Order requests ────────────────────────────────────────────────────────────

test('institution order request requires uuid for each row id', function () {
    $rules = makeRules(InstitutionOrderRequest::class, [['id' => 'bad', 'order' => 1]]);
    assertFails($rules, [['id' => 'bad', 'order' => 1]], '0.id');
});

test('institution order request requires integer for each row order', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(InstitutionOrderRequest::class, [['id' => $institution->id, 'order' => 'bad']]);
    assertFails($rules, [['id' => $institution->id, 'order' => 'bad']], '0.order');
});

test('resource order request requires uuid for each row id', function () {
    $rules = makeRules(ResourceOrderRequest::class, [['id' => 'bad', 'order' => 1]]);
    assertFails($rules, [['id' => 'bad', 'order' => 1]], '0.id');
});

test('resource group order request requires uuid for each row id', function () {
    $rules = makeRules(ResourceGroupOrderRequest::class, [['id' => 'bad', 'order' => 1]]);
    assertFails($rules, [['id' => 'bad', 'order' => 1]], '0.id');
});

// ── HappeningRequest (via StoreHappeningRequest) ──────────────────────────────

test('store happening request requires start_date', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, ['resource_id' => $resource->id, 'user_id_01' => $user->id]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_time' => '10:00', 'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'start_date');
});

test('store happening request rejects invalid date format', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, ['resource_id' => $resource->id, 'user_id_01' => $user->id]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_date' => '2026-06-10', 'start_time' => '10:00', // wrong format, should be d.m.Y
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'start_date');
});

test('store happening request requires resource_id', function () {
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, ['user_id_01' => $user->id]);
    assertFails($rules, [
        'user_id_01' => $user->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'resource_id');
});

test('store happening request requires user_id_01', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $rules = makeRules(StoreHappeningRequest::class, ['resource_id' => $resource->id]);
    assertFails($rules, [
        'resource_id' => $resource->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'user_id_01');
});

test('store happening request requires is_verified', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, ['resource_id' => $resource->id, 'user_id_01' => $user->id]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00',
    ], 'is_verified');
});

// ── StoreResourceRequest ──────────────────────────────────────────────────────

test('store resource request requires resource_group_id', function () {
    $rules = makeRules(StoreResourceRequest::class, []);
    assertFails($rules, [
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => true, 'is_verification_required' => false,
    ], 'resource_group_id');
});

test('store resource request rejects non-uuid resource_group_id', function () {
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => 'bad']);
    assertFails($rules, [
        'resource_group_id' => 'bad',
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => true, 'is_verification_required' => false,
    ], 'resource_group_id');
});

test('store resource request requires is_active', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_verification_required' => false,
    ], 'is_active');
});

test('store resource request requires is_verification_required', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => true,
    ], 'is_verification_required');
});

test('store resource request rejects non-positive capacity', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 0,
        'is_active' => true, 'is_verification_required' => false,
    ], 'capacity');
});

test('store resource request rejects non-numeric capacity', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 'many',
        'is_active' => true, 'is_verification_required' => false,
    ], 'capacity');
});

test('store resource request rejects invalid location_uri', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => true, 'is_verification_required' => false,
        'location_uri' => 'not-a-url',
    ], 'location_uri');
});

// ── ImportUsersRequest ────────────────────────────────────────────────────────

test('import users request requires id (user group uuid)', function () {
    $rules = makeRules(ImportUsersRequest::class, []);
    assertFails($rules, ['users' => [['name' => 'Alice', 'email' => 'a@b.com']]], 'id');
});

test('import users request rejects non-uuid id', function () {
    $rules = makeRules(ImportUsersRequest::class, ['id' => 'bad']);
    assertFails($rules, ['id' => 'bad', 'users' => [['name' => 'Alice']]], 'id');
});

test('import users request requires users array', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, ['id' => $group->id]);
    assertFails($rules, ['id' => $group->id], 'users');
});

test('import users request requires users.*.name', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, ['id' => $group->id, 'users' => [[]]]);
    assertFails($rules, ['id' => $group->id, 'users' => [['email' => 'a@b.com']]], 'users.0.name');
});

test('import users request prohibits mixing date and text valid_from', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, [
        'id' => $group->id,
        'valid_from_date' => '2026-01-01',
        'valid_from_text' => 'January 2026',
    ]);
    assertFails($rules, [
        'id' => $group->id,
        'users' => [['name' => 'Alice']],
        'valid_from_date' => '2026-01-01',
        'valid_from_text' => 'January 2026',
    ], 'valid_from_date');
});

test('import users request requires valid date format for valid_from_date', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, [
        'id' => $group->id,
        'valid_from_date' => 'not-a-date',
    ]);
    assertFails($rules, [
        'id' => $group->id,
        'users' => [['name' => 'Alice']],
        'valid_from_date' => 'not-a-date',
    ], 'valid_from_date');
});

// ── RemoveUsersFromUserGroupRequest ───────────────────────────────────────────

test('remove users from user group request requires id', function () {
    $rules = makeRules(RemoveUsersFromUserGroupRequest::class, []);
    assertFails($rules, ['users' => []], 'id');
});

test('remove users from user group request rejects non-uuid id', function () {
    $rules = makeRules(RemoveUsersFromUserGroupRequest::class, ['id' => 'bad']);
    assertFails($rules, ['id' => 'bad', 'users' => []], 'id');
});

test('remove users from user group request requires users array', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(RemoveUsersFromUserGroupRequest::class, ['id' => $group->id]);
    assertFails($rules, ['id' => $group->id], 'users');
});

test('remove users from user group request requires valid uuid for each user', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id,
        'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(RemoveUsersFromUserGroupRequest::class, ['id' => $group->id]);
    assertFails($rules, ['id' => $group->id, 'users' => ['not-a-uuid']], 'users.0');
});

// ── StoreClosingRequest / UpdateClosingRequest ────────────────────────────────

test('store closing request requires closable_id', function () {
    $rules = makeRules(StoreClosingRequest::class, []);
    assertFails($rules, [
        'closable_type' => 'institution',
        'start_date' => '10.06.2026', 'start_time' => '09:00',
        'end_date' => '10.06.2026', 'end_time' => '10:00',
    ], 'closable_id');
});

test('store closing request requires start_date', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(StoreClosingRequest::class, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
    ]);
    assertFails($rules, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
        'start_time' => '09:00', 'end_date' => '10.06.2026', 'end_time' => '10:00',
    ], 'start_date');
});

test('store closing request requires end_date', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(StoreClosingRequest::class, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
    ]);
    assertFails($rules, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
        'start_date' => '10.06.2026', 'start_time' => '09:00', 'end_time' => '10:00',
    ], 'end_date');
});

test('update closing request requires id', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(UpdateClosingRequest::class, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
    ]);
    assertFails($rules, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
        'start_date' => '10.06.2026', 'start_time' => '09:00',
        'end_date' => '10.06.2026', 'end_time' => '10:00',
    ], 'id');
});

// ── PermissionGroupRequest / PermissionRequest ────────────────────────────────

test('permission group request requires non-empty translated name', function () {
    $rules = makeRules(PermissionGroupRequest::class, ['name' => []]);
    assertFails($rules, ['name' => []], 'name');
});

test('permission request requires non-empty translated name', function () {
    $rules = makeRules(PermissionRequest::class, ['name' => []]);
    assertFails($rules, ['name' => []], 'name');
});

// ── UserRequest ───────────────────────────────────────────────────────────────

test('user request requires is_system_user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, [], $admin);
    assertFails($rules, ['name' => 'alice', 'is_admin' => false, 'roles' => []], 'is_system_user');
});

test('user request rejects non-boolean is_system_user', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => 'yes'], $admin);
    assertFails($rules, ['is_system_user' => 'yes', 'is_admin' => false], 'is_system_user');
});

test('user request requires is_admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => false], $admin);
    assertFails($rules, ['is_system_user' => false], 'is_admin');
});

test('user request rejects non-boolean is_admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => false], $admin);
    assertFails($rules, ['is_system_user' => false, 'is_admin' => 'yes'], 'is_admin');
});

test('user request requires name when is_system_user is accepted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'is_set_password' => false, 'roles' => [],
    ], 'name');
});

test('user request requires email when is_system_user is accepted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'is_set_password' => false, 'roles' => [],
    ], 'email');
});

test('user request rejects invalid email', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'email' => 'not-an-email', 'is_set_password' => false, 'roles' => [],
    ], 'email');
});

test('user request requires name to be minimum 3 characters', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'ab', 'email' => 'a@b.com', 'is_set_password' => false, 'roles' => [],
    ], 'name');
});

test('user request requires roles to be an array', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => false], $admin);
    assertFails($rules, [
        'is_system_user' => false, 'is_admin' => false, 'roles' => 'not-array',
    ], 'roles');
});

// ── Additional: RequiredWithTranslationRule fields ────────────────────────────

test('institution request rejects empty title translations', function () {
    $rules = makeRules(InstitutionRequest::class, ['title' => [], 'is_active' => false]);
    assertFails($rules, ['title' => [], 'short_title' => 'L', 'slug' => 'lib', 'is_active' => false], 'title');
});

test('resource group request rejects empty title translations', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, [
        'institution_id' => $institution->id, 'title' => [], 'slug' => 'rooms', 'is_active' => false,
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'title' => [], 'slug' => 'rooms', 'is_active' => false,
    ], 'title');
});

test('resource group request rejects empty term_singular translations', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(ResourceGroupRequest::class, [
        'institution_id' => $institution->id, 'slug' => 'rooms', 'is_active' => false,
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'), 'slug' => 'rooms',
        'is_active' => false, 'term_singular' => [],
    ], 'term_singular');
});

test('store resource request rejects empty title translations', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id, 'title' => []]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id, 'title' => [],
        'capacity' => 1, 'is_active' => true, 'is_verification_required' => false,
    ], 'title');
});

test('store user group request rejects empty title translations', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(StoreUserGroupRequest::class, [
        'institution_id' => $institution->id, 'title' => [],
    ]);
    assertFails($rules, ['institution_id' => $institution->id, 'title' => []], 'title');
});

test('update user group request rejects empty title translations', function () {
    $group = UserGroup::create(['institution_id' => Institution::factory()->create()->id, 'title' => ['en' => 'G']]);
    $rules = makeRules(UpdateUserGroupRequest::class, ['id' => $group->id, 'title' => []]);
    assertFails($rules, ['id' => $group->id, 'title' => []], 'title');
});

test('role request rejects empty name translations', function () {
    $rules = makeRules(RoleRequest::class, ['name' => []]);
    assertFails($rules, ['name' => []], 'name');
});

test('mail content request rejects non-boolean is_active', function () {
    $institution = Institution::factory()->create();
    $mailType = MailType::query()->first();
    $rules = makeRules(MailContentRequest::class, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
    ]);
    assertFails($rules, [
        'institution_id' => $institution->id, 'mail_type_id' => $mailType?->id,
        'subject' => 'Test', 'is_active' => 'yes',
    ], 'is_active');
});

// ── Additional: HappeningRequest conditional rules ────────────────────────────

test('store happening request requires verifier when verification is required and not verified', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id, 'is_verified' => false,
    ]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'verifier');
});

test('store happening request requires user_id_02 when verification is required and is_verified is true', function () {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();
    $resource = Resource::factory()->for($resourceGroup, 'resource_group')->create([
        'is_verification_required' => true,
    ]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id, 'is_verified' => true,
    ]);
    // Explicitly include user_id_02 = null so 'sometimes' fires and required_if is evaluated
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00',
        'is_verified' => true, 'user_id_02' => null,
    ], 'user_id_02');
});

test('store happening request rejects non-boolean is_verified', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $user = User::factory()->create();
    $rules = makeRules(StoreHappeningRequest::class, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id, 'is_verified' => 'yes',
    ]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => $user->id,
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => 'yes',
    ], 'is_verified');
});

test('update happening request rejects non-uuid user_id_01', function () {
    $resource = Resource::factory()->for(
        ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create(),
        'resource_group'
    )->create(['is_verification_required' => false]);
    $rules = makeRules(UpdateHappeningRequest::class, [
        'resource_id' => $resource->id, 'user_id_01' => 'bad', 'is_verified' => false,
    ]);
    assertFails($rules, [
        'resource_id' => $resource->id, 'user_id_01' => 'bad',
        'start_date' => '10.06.2026', 'start_time' => '10:00',
        'end_date' => '10.06.2026', 'end_time' => '11:00', 'is_verified' => false,
    ], 'user_id_01');
});

// ── Additional: UserRequest conditional rules ────────────────────────────────

test('user request requires password when is_set_password is accepted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true, 'is_set_password' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'email' => 'a@b.com',
        'is_set_password' => true, 'roles' => [],
    ], 'password');
});

test('user request requires password_confirm when is_set_password is accepted', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true, 'is_set_password' => true], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'email' => 'a@b.com',
        'is_set_password' => true, 'password' => 'secret123', 'roles' => [],
    ], 'password_confirm');
});

test('user request rejects is_set_password non-boolean when is_system_user is true', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $rules = makeRules(UserRequest::class, ['is_system_user' => true, 'is_set_password' => 'yes'], $admin);
    assertFails($rules, [
        'is_system_user' => true, 'is_admin' => false,
        'name' => 'alice.user', 'email' => 'a@b.com',
        'is_set_password' => 'yes', 'roles' => [],
    ], 'is_set_password');
});

// ── Additional: ResourceRequest business_hours rules ─────────────────────────

test('store resource request requires business_hours when is_active is true', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, [
        'resource_group_id' => $resourceGroup->id, 'business_hours' => [],
    ]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => true, 'is_verification_required' => false, 'business_hours' => [],
    ], 'business_hours');
});

test('store resource request rejects non-array business_hours', function () {
    $resourceGroup = ResourceGroup::factory()->for(Institution::factory()->create(), 'institution')->create();
    $rules = makeRules(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id]);
    assertFails($rules, [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'), 'capacity' => 1,
        'is_active' => false, 'is_verification_required' => false, 'business_hours' => 'bad',
    ], 'business_hours');
});

// ── Additional: Closing request time format rules ────────────────────────────

test('store closing request rejects wrong start_time format', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(StoreClosingRequest::class, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
    ]);
    assertFails($rules, [
        'closable_id' => $institution->id, 'closable_type' => 'institution',
        'start_date' => '10.06.2026', 'start_time' => '10:00:00', // wrong format
        'end_date' => '10.06.2026', 'end_time' => '11:00',
    ], 'start_time');
});

test('store closing request requires closable_type', function () {
    $institution = Institution::factory()->create();
    $rules = makeRules(StoreClosingRequest::class, ['closable_id' => $institution->id]);
    assertFails($rules, [
        'closable_id' => $institution->id,
        'start_date' => '10.06.2026', 'start_time' => '09:00',
        'end_date' => '10.06.2026', 'end_time' => '10:00',
    ], 'closable_type');
});

test('update closing request rejects non-uuid closable_id', function () {
    $rules = makeRules(UpdateClosingRequest::class, ['closable_id' => 'bad']);
    assertFails($rules, [
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'closable_id' => 'bad', 'closable_type' => 'institution',
        'start_date' => '10.06.2026', 'start_time' => '09:00',
        'end_date' => '10.06.2026', 'end_time' => '10:00',
    ], 'closable_id');
});

// ── Additional: ImportUsersRequest ───────────────────────────────────────────

test('import users request requires users to be an array not a string', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id, 'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, ['id' => $group->id, 'users' => 'bad']);
    assertFails($rules, ['id' => $group->id, 'users' => 'bad'], 'users');
});

test('import users request prohibits mixing date and text valid_until', function () {
    $group = UserGroup::create([
        'institution_id' => Institution::factory()->create()->id, 'title' => ['en' => 'G'],
    ]);
    $rules = makeRules(ImportUsersRequest::class, [
        'id' => $group->id,
        'valid_until_date' => '2026-12-31',
        'valid_until_text' => 'December 2026',
    ]);
    assertFails($rules, [
        'id' => $group->id, 'users' => [['name' => 'Alice']],
        'valid_until_date' => '2026-12-31', 'valid_until_text' => 'December 2026',
    ], 'valid_until_date');
});
