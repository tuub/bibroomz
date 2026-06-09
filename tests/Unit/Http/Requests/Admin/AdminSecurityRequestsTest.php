<?php

use App\Http\Requests\Admin\AdminRouteRequest;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\ClosableContextRequest;
use App\Http\Requests\Admin\ClosingIdRequest;
use App\Http\Requests\Admin\DeleteClosingRequest;
use App\Http\Requests\Admin\DeleteHappeningRequest;
use App\Http\Requests\Admin\DeleteInstitutionRequest;
use App\Http\Requests\Admin\DeleteMailContentRequest;
use App\Http\Requests\Admin\DeleteResourceGroupRequest;
use App\Http\Requests\Admin\DeleteResourceRequest;
use App\Http\Requests\Admin\DeleteRoleRequest;
use App\Http\Requests\Admin\DeleteUserGroupRequest;
use App\Http\Requests\Admin\DeleteUserRequest;
use App\Http\Requests\Admin\HappeningIdRequest;
use App\Http\Requests\Admin\InstitutionContextRequest;
use App\Http\Requests\Admin\InstitutionIdRequest;
use App\Http\Requests\Admin\MailContentIdRequest;
use App\Http\Requests\Admin\ResourceGroupContextRequest;
use App\Http\Requests\Admin\ResourceGroupIdRequest;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Http\Requests\Admin\ResourceIdRequest;
use App\Http\Requests\Admin\RoleIdRequest;
use App\Http\Requests\Admin\SettingableContextRequest;
use App\Http\Requests\Admin\SettingIdRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UnbanUserRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Http\Requests\Admin\UserGroupIdRequest;
use App\Http\Requests\Admin\UserIdRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

covers(
    AdminRouteRequest::class,
    InstitutionContextRequest::class,
    InstitutionIdRequest::class,
    ResourceGroupContextRequest::class,
    ResourceGroupIdRequest::class,
    ResourceIdRequest::class,
    ClosingIdRequest::class,
    HappeningIdRequest::class,
    DeleteClosingRequest::class,
    DeleteHappeningRequest::class,
    DeleteInstitutionRequest::class,
    DeleteResourceRequest::class,
    DeleteResourceGroupRequest::class,
    BanUserRequest::class,
    UnbanUserRequest::class,
    DeleteUserRequest::class,
    UserIdRequest::class,
    DeleteUserGroupRequest::class,
    UserGroupIdRequest::class,
    DeleteRoleRequest::class,
    RoleIdRequest::class,
    SettingIdRequest::class,
    DeleteMailContentRequest::class,
    MailContentIdRequest::class,
    SettingableContextRequest::class,
    ClosableContextRequest::class
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

test('findModelOrFail resolves the expected institution from the request identifier', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $this->grantPermission($actor, $institution, 'edit_institutions');

    $request = buildAdminFormRequest(InstitutionIdRequest::class, ['id' => $institution->id], $actor);

    // Wire up the validator so validated() is available, matching real request lifecycle.
    $validator = Validator::make(
        $request->validationData(),
        $request->rules(),
    );
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id);
});
