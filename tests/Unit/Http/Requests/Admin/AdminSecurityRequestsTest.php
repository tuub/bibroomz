<?php

use App\Http\Requests\Admin\AdminRouteRequest;
use App\Http\Requests\Admin\InstitutionRequest;
use App\Http\Requests\Admin\MailContentRequest;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Http\Requests\Admin\ResourceRequest;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Requests\Admin\StoreClosingRequest;
use App\Http\Requests\Admin\StoreResourceRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateClosingRequest;
use App\Http\Requests\Admin\UpdateHappeningRequest;
use App\Http\Requests\Admin\UpdateResourceRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Library\Utility;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\CarbonImmutable;
use Database\Seeders\MailTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(
    AdminRouteRequest::class,
    ResourceGroupRequest::class,
    StoreUserGroupRequest::class,
    UpdateUserGroupRequest::class,
    InstitutionRequest::class,
    MailContentRequest::class,
    UpdateHappeningRequest::class,
    UpdateResourceRequest::class,
    UserRequest::class,
    RoleRequest::class,
    StoreClosingRequest::class,
    ResourceRequest::class,
    StoreResourceRequest::class,
    UpdateClosingRequest::class,
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('resource group request allows create for authorized institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'create_resource_groups');

    $request = buildAdminFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('resource group request rejects move without target create permission', function (): void {
    $sourceInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $sourceInstitution->id]);

    $this->grantPermission($user, $sourceInstitution, 'edit_resource_groups');

    $request = buildAdminFormRequest(
        ResourceGroupRequest::class,
        ['id' => $resourceGroup->id, 'institution_id' => $targetInstitution->id],
        $user
    );
    expect($request->authorize())->toBeFalse();
});

test('resource group request accepts move when user can edit source and create target', function (): void {
    $sourceInstitution = Institution::factory()->create();
    $targetInstitution = Institution::factory()->create();
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $sourceInstitution->id]);

    $this->grantPermission($user, $sourceInstitution, 'edit_resource_groups');
    $this->grantPermission($user, $targetInstitution, 'create_resource_groups');

    $request = buildAdminFormRequest(
        ResourceGroupRequest::class,
        ['id' => $resourceGroup->id, 'institution_id' => $targetInstitution->id],
        $user
    );
    expect($request->authorize())->toBeTrue();
});

test('resource group request validation rejects foreign user group ids', function (): void {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    $foreignUserGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Foreign Group'),
        'institution_id' => $otherInstitution->id,
    ]);

    $request = new ResourceGroupRequest;
    $request->merge(['institution_id' => $institution->id]);

    $validator = Validator::make([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Rooms'],
        'slug' => 'rooms',
        'term_singular' => ['en' => 'Room'],
        'term_plural' => ['en' => 'Rooms'],
        'description' => ['en' => 'Description'],
        'is_active' => true,
        'user_groups' => [$foreignUserGroup->id],
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKey('user_groups.0');
});

test('store user group request authorizes only users with create permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeFalse();

    $this->grantPermission($user, $institution, 'create_user_groups');

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('update user group request authorizes only users with update permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $userGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Protected Group'),
        'institution_id' => $institution->id,
    ]);

    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, ['id' => $userGroup->id], $user);
    expect($request->authorize())->toBeFalse();

    $this->grantPermission($user, $institution, 'edit_user_groups');

    $request = buildAdminFormRequest(UpdateUserGroupRequest::class, ['id' => $userGroup->id], $user);
    expect($request->authorize())->toBeTrue();
});

// ── InstitutionRequest: null-user false branch ────────────────────────────────

test('institution request authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(InstitutionRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

test('institution request authorize returns false when user cannot create institution', function (): void {
    $user = User::factory()->create(['is_admin' => false]);
    $request = buildFormRequest(InstitutionRequest::class, [], $user);
    expect($request->authorize())->toBeFalse();
});

// ── MailContentRequest: null-user false branch ────────────────────────────────

test('mail content request authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(MailContentRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

// ── UpdateHappeningRequest: null-user / cross-group auth paths ────────────────

test('update happening request authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(UpdateHappeningRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

test('update happening request authorize uses cross-group create permission when resource group changes', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $otherResourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource1 = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $resource2 = Resource::factory()->create(['resource_group_id' => $otherResourceGroup->id]);
    $user = User::factory()->create();

    $happening = Happening::create([
        'user_id_01' => $user->id,
        'resource_id' => $resource1->id,
        'is_verified' => false,
        'verifier' => 'verifier',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'label' => Utility::getTranslatable('Study'),
    ]);

    // Request to move happening to a different resource group (cross-group path)
    $request = buildAdminFormRequest(UpdateHappeningRequest::class, [
        'id' => $happening->id,
        'resource_id' => $resource2->id,
    ], $user);

    // User doesn't have adminCreate permission → false
    expect($request->authorize())->toBeFalse();
});

// ── UpdateResourceRequest: null-user / cross-group auth paths ─────────────────

test('update resource request authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(UpdateResourceRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

test('update resource request authorize uses cross-group create permission when group changes', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup1 = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resourceGroup2 = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup1->id]);
    $user = User::factory()->create();

    // Request to move resource to a different group (cross-group path)
    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup2->id,
    ], $user);

    // User doesn't have create permission on target group → false
    expect($request->authorize())->toBeFalse();
});

// ── UserRequest: null-user, invalid role format, and inputRoles normalization ──

test('user request authorize returns false when no user is authenticated', function (): void {
    $request = buildFormRequest(UserRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

test('user request authorize returns false when roles contains entry without institution_id', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    // roles array with array missing institution_id → authorize returns false
    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [['role_id' => 'some-role-id']],
    ], $user);
    expect($request->authorize())->toBeFalse();
});

test('user request inputRoles skips non-array role entries', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    // roles is a non-array value → inputRoles returns []
    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => null,
        'roles' => 'not-an-array',
    ], $user);

    // authorize should proceed past the roles loop (empty) and check create permission
    // admin can create users
    expect($request->authorize())->toBeTrue();
});

// ── RoleRequest: null-user and edit-role paths ────────────────────────────────

test('role request authorize returns false when no user authenticated', function (): void {
    $request = buildFormRequest(RoleRequest::class, []);
    expect($request->authorize())->toBeFalse();
});

test('role request authorize uses edit path when role exists', function (): void {
    $role = Role::create([
        'name' => Utility::getTranslatable('TestRole'),
        'description' => Utility::getTranslatable('Desc'),
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(RoleRequest::class, ['id' => $role->id], $user);
    expect($request->authorize())->toBeTrue();
});

// ── ResourceGroupRequest: update path with no update permission ────

test('resource group request authorize returns false when user is not authenticated', function (): void {
    $institution = Institution::factory()->create();
    // buildFormRequest with no user → userModel() returns null
    $request = buildFormRequest(
        ResourceGroupRequest::class,
        ['institution_id' => $institution->id],
    );
    expect($request->authorize())->toBeFalse();
});

test('resource group request authorize returns false when user cannot update the resource group', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);

    // User has create permission but NOT edit (update) permission
    $this->grantPermission($user, $institution, 'create_resource_groups');

    $request = buildAdminFormRequest(
        ResourceGroupRequest::class,
        ['id' => $resourceGroup->id, 'institution_id' => $institution->id],
        $user
    );
    expect($request->authorize())->toBeFalse();
});

// ── RoleRequest: prepareForValidation and role() accessor ─────────────────────

test('role request prepareForValidation merges permissions as empty array when not provided', function (): void {
    $request = buildFormRequest(RoleRequest::class, ['name' => Utility::getTranslatable('Editor')]);

    $reflection = new ReflectionMethod($request, 'prepareForValidation');
    $reflection->invoke($request);

    expect($request->input('permissions'))->toBe([]);
});

test('role request role accessor returns the correct role model', function (): void {
    $role = Role::create([
        'name' => Utility::getTranslatable('AccessorRole'),
        'description' => Utility::getTranslatable('Desc'),
    ]);
    $user = User::factory()->create();

    $data = [
        'id' => $role->id,
        'name' => Utility::getTranslatable('AccessorRole'),
        'description' => Utility::getTranslatable('Desc'),
        'permissions' => [],
    ];
    $request = buildAdminFormRequest(RoleRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->role()->id)->toBe($role->id);
});

// ── StoreClosingRequest: closable() returns null when no id given ─────────────

test('store closing request authorize returns false when closable cannot be resolved', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        // no closable_id → closable() returns null
    ], $user);

    expect($request->authorize())->toBeFalse();
});

test('store closing request authorize returns true when user can create closing for resolved closable', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_closings');

    $request = buildAdminFormRequest(StoreClosingRequest::class, [
        'closable_type' => 'institution',
        'closable_id' => $institution->id,
    ], $user);

    expect($request->authorize())->toBeTrue();
});

// ── InstitutionRequest: update authorize path, prepareForValidation, accessors ──

test('institution request authorize returns true when user can update existing institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_institutions');

    $request = buildAdminFormRequest(InstitutionRequest::class, ['id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('institution request prepareForValidation merges week_days as empty array when not provided', function (): void {
    $request = buildFormRequest(InstitutionRequest::class, []);
    $reflection = new ReflectionMethod($request, 'prepareForValidation');
    $reflection->invoke($request);
    expect($request->input('week_days'))->toBe([]);
});

test('institution request institution accessor and institutionData and weekDays return correct values', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $institution->id,
        'title' => Utility::getTranslatable('Lib'),
        'short_title' => 'LIB',
        'slug' => $institution->slug,
        'is_active' => true,
        'week_days' => [1, 2],
    ];
    $request = buildAdminFormRequest(InstitutionRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id)
        ->and($request->institutionData())->toHaveKey('slug')
        ->and($request->weekDays())->toBe([1, 2]);
});

// ── MailContentRequest: edit-mail and create-for-institution authorize paths, accessors ──

test('mail content request authorize returns true when user can edit existing mail content', function (): void {
    $this->seed(MailTypeSeeder::class);
    $institution = Institution::factory()->create();
    $mailType = MailType::firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Subject',
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_mails');

    $request = buildAdminFormRequest(MailContentRequest::class, ['id' => $mail->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('mail content request authorize returns true when user can create for institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'create_mails');

    $request = buildAdminFormRequest(MailContentRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('mail content request institution and mailContent accessors return correct values', function (): void {
    $this->seed(MailTypeSeeder::class);
    $institution = Institution::factory()->create();
    $mailType = MailType::firstOrFail();
    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Subject',
        'is_active' => true,
    ]);
    $user = User::factory()->create(['is_admin' => true]);

    $data = [
        'id' => $mail->id,
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Subject',
        'is_active' => true,
    ];
    $request = buildAdminFormRequest(MailContentRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id)
        ->and($request->mailContent()->id)->toBe($mail->id);
});

// ── ResourceGroupRequest: same-institution authorize path, prepareForValidation, resourceGroup ──

test('resource group request authorize returns true when user can update and resource group is in same institution', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $this->grantPermission($user, $institution, 'edit_resource_groups');

    $request = buildAdminFormRequest(
        ResourceGroupRequest::class,
        ['id' => $resourceGroup->id, 'institution_id' => $institution->id],
        $user
    );
    expect($request->authorize())->toBeTrue();
});

test('resource group request prepareForValidation merges user_groups as empty array when not provided', function (): void {
    $request = buildFormRequest(ResourceGroupRequest::class, []);
    $reflection = new ReflectionMethod($request, 'prepareForValidation');
    $reflection->invoke($request);
    expect($request->input('user_groups'))->toBe([]);
});

test('resource group request resourceGroup accessor returns the model', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);

    $data = [
        'id' => $resourceGroup->id,
        'institution_id' => $institution->id,
        'title' => Utility::getTranslatable('Rooms'),
        'slug' => $resourceGroup->slug,
        'term_singular' => Utility::getTranslatable('Room'),
        'term_plural' => Utility::getTranslatable('Rooms'),
        'description' => Utility::getTranslatable('Desc'),
        'is_active' => true,
        'user_groups' => [],
    ];
    $request = buildAdminFormRequest(ResourceGroupRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceGroup()->id)->toBe($resourceGroup->id);
});

// ── ResourceRequest: prepareForValidation, resourceData, businessHours, resourceGroup accessors ──

test('resource request prepareForValidation merges business_hours as empty array when not provided', function (): void {
    $request = buildFormRequest(StoreResourceRequest::class, []);
    $reflection = new ReflectionMethod($request, 'prepareForValidation');
    $reflection->invoke($request);
    expect($request->input('business_hours'))->toBe([]);
});

test('resource request resourceData businessHours and resourceGroup accessors return correct values', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);

    $data = [
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildAdminFormRequest(StoreResourceRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resourceData())->toHaveKey('title')
        ->and($request->businessHours())->toBe([])
        ->and($request->resourceGroup()?->id)->toBe($resourceGroup->id);
});

// ── StoreResourceRequest: authorize ──────────────────────────────────────────

test('store resource request authorize returns true when user has create permission', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $this->grantPermission($user, $institution, 'create_resources');

    $request = buildAdminFormRequest(StoreResourceRequest::class, ['resource_group_id' => $resourceGroup->id], $user);
    expect($request->authorize())->toBeTrue();
});

// ── UpdateClosingRequest: authorize, closing, closingOrNull, closableType, closableId ──

test('update closing request authorize returns true when user can edit closing', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Maintenance'),
    ]);
    $this->grantPermission($user, $institution, 'edit_closings');

    $request = buildAdminFormRequest(UpdateClosingRequest::class, ['id' => $closing->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('update closing request authorize returns false when user cannot edit', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Maintenance'),
    ]);

    $request = buildAdminFormRequest(UpdateClosingRequest::class, ['id' => $closing->id], $user);
    expect($request->authorize())->toBeFalse();
});

test('update closing request closing closingOrNull closableType and closableId accessors work', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $closing = Closing::create([
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start' => now()->addDay(),
        'end' => now()->addDay()->addHour(),
        'description' => Utility::getTranslatable('Maintenance'),
    ]);

    $data = [
        'id' => $closing->id,
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(UpdateClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closing()->id)->toBe($closing->id)
        ->and($request->closingOrNull()?->id)->toBe($closing->id)
        ->and($request->closableType())->toBe(Institution::class)
        ->and($request->closableId())->toBe($institution->id);
});

// ── StoreClosingRequest: closableType and closableId accessors ────────────────

test('store closing request closableType and closableId accessors return correct values', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $data = [
        'closable_id' => $institution->id,
        'closable_type' => Institution::class,
        'start_date' => now()->addDay()->format('d.m.Y'),
        'start_time' => '09:00',
        'end_date' => now()->addDay()->format('d.m.Y'),
        'end_time' => '10:00',
    ];
    $request = buildAdminFormRequest(StoreClosingRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->closableType())->toBe(Institution::class)
        ->and($request->closableId())->toBe($institution->id);
});

// ── UpdateHappeningRequest: same-group authorize path, happening accessor ─────

test('update happening request authorize returns true when user can adminUpdate in same resource group', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_happenings');

    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $owner = User::factory()->create();
    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => 'verifier.user',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ]);

    $request = buildAdminFormRequest(UpdateHappeningRequest::class, [
        'id' => $happening->id,
        'resource_id' => $resource->id,
    ], $user);
    expect($request->authorize())->toBeTrue();
});

test('update happening request happening accessor returns the model', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $owner = User::factory()->create();
    $happening = Happening::create([
        'user_id_01' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => 'verifier.user',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Study'),
    ]);

    $data = [
        'id' => $happening->id,
        'resource_id' => $resource->id,
        'user_id_01' => $owner->id,
        'start_date' => CarbonImmutable::now()->addHour()->format('d.m.Y'),
        'start_time' => CarbonImmutable::now()->addHour()->format('H:i'),
        'end_date' => CarbonImmutable::now()->addHours(2)->format('d.m.Y'),
        'end_time' => CarbonImmutable::now()->addHours(2)->format('H:i'),
        'is_verified' => false,
        'verifier' => 'verifier.user',
    ];
    $request = buildAdminFormRequest(UpdateHappeningRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->happening()->id)->toBe($happening->id);
});

// ── UpdateResourceRequest: same-group authorize path, resource accessor ────────

test('update resource request authorize returns true when user can edit resource in same resource group', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $this->grantPermission($user, $institution, 'edit_resources');

    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

    $request = buildAdminFormRequest(UpdateResourceRequest::class, [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
    ], $user);
    expect($request->authorize())->toBeTrue();
});

test('update resource request resource accessor returns the model', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create(['is_admin' => true]);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

    $data = [
        'id' => $resource->id,
        'resource_group_id' => $resourceGroup->id,
        'title' => Utility::getTranslatable('Desk'),
        'location' => Utility::getTranslatable('Floor 1'),
        'description' => Utility::getTranslatable('A desk'),
        'capacity' => 2,
        'is_active' => false,
        'is_verification_required' => false,
        'business_hours' => [],
    ];
    $request = buildAdminFormRequest(UpdateResourceRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->resource()->id)->toBe($resource->id);
});

// ── UserRequest: authorize role-checking and update path, prepareForValidation, accessors ──

test('user request authorize returns false when a role institution cannot be found', function (): void {
    $user = User::factory()->create(['is_admin' => true]);

    $request = buildAdminFormRequest(UserRequest::class, [
        'roles' => [['institution_id' => 'non-existent-uuid', 'role_id' => 'some-role']],
    ], $user);
    expect($request->authorize())->toBeFalse();
});

test('user request authorize returns true for update when user can update target', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create();

    $request = buildAdminFormRequest(UserRequest::class, [
        'id' => $target->id,
        'roles' => [],
    ], $user);
    expect($request->authorize())->toBeTrue();
});

test('user request prepareForValidation merges roles as empty array when not provided', function (): void {
    $request = buildFormRequest(UserRequest::class, []);
    $reflection = new ReflectionMethod($request, 'prepareForValidation');
    $reflection->invoke($request);
    expect($request->input('roles'))->toBe([]);
});

test('user request roles and userData and targetUser accessors return correct values', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['name' => 'john.doe', 'email' => 'john@example.test']);

    $data = [
        'id' => $target->id,
        'is_system_user' => false,
        'is_admin' => false,
        'is_set_password' => false,
        'roles' => [],
    ];
    $request = buildAdminFormRequest(UserRequest::class, $data, $user);
    $validator = Validator::make($request->validationData(), $request->rules());
    $validator->passes();
    $request->setValidator($validator);

    expect($request->roles())->toBe([])
        ->and($request->userData())->toHaveKey('id')
        ->and($request->targetUser()->id)->toBe($target->id);
});
