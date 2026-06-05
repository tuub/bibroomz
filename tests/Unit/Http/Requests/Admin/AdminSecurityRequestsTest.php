<?php

covers(
    App\Http\Requests\Admin\AdminRouteRequest::class,
    App\Http\Requests\Admin\InstitutionContextRequest::class,
    App\Http\Requests\Admin\InstitutionIdRequest::class,
    App\Http\Requests\Admin\ResourceGroupContextRequest::class,
    App\Http\Requests\Admin\ResourceGroupIdRequest::class,
    App\Http\Requests\Admin\ResourceIdRequest::class,
    App\Http\Requests\Admin\ClosingIdRequest::class,
    App\Http\Requests\Admin\HappeningIdRequest::class,
    App\Http\Requests\Admin\DeleteClosingRequest::class,
    App\Http\Requests\Admin\DeleteHappeningRequest::class,
    App\Http\Requests\Admin\DeleteInstitutionRequest::class,
    App\Http\Requests\Admin\DeleteResourceRequest::class,
    App\Http\Requests\Admin\DeleteResourceGroupRequest::class,
    App\Http\Requests\Admin\BanUserRequest::class,
    App\Http\Requests\Admin\UnbanUserRequest::class,
    App\Http\Requests\Admin\DeleteUserRequest::class,
    App\Http\Requests\Admin\UserIdRequest::class,
    App\Http\Requests\Admin\DeleteUserGroupRequest::class,
    App\Http\Requests\Admin\UserGroupIdRequest::class,
    App\Http\Requests\Admin\DeleteRoleRequest::class,
    App\Http\Requests\Admin\RoleIdRequest::class,
    App\Http\Requests\Admin\SettingIdRequest::class,
    App\Http\Requests\Admin\DeleteMailContentRequest::class,
    App\Http\Requests\Admin\MailContentIdRequest::class,
    App\Http\Requests\Admin\SettingableContextRequest::class,
    App\Http\Requests\Admin\ClosableContextRequest::class
);

use App\Http\Requests\Admin\InstitutionIdRequest;
use App\Http\Requests\Admin\ResourceGroupRequest;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupRequest;
use App\Library\Utility;
use App\Models\Institution;
use App\Models\ResourceGroup;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('resource group request allows create for authorized institution', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $this->grantPermission($user, $institution, 'create_resource_groups');

    $request = buildAdminFormRequest(ResourceGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('resource group request rejects move without target create permission', function () {
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

test('resource group request accepts move when user can edit source and create target', function () {
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

test('resource group request validation rejects foreign user group ids', function () {
    $institution = Institution::factory()->create();
    $otherInstitution = Institution::factory()->create();
    $foreignUserGroup = UserGroup::create([
        'title' => Utility::getTranslatable('Foreign Group'),
        'institution_id' => $otherInstitution->id,
    ]);

    $request = new ResourceGroupRequest();
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

test('store user group request authorizes only users with create permission', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeFalse();

    $this->grantPermission($user, $institution, 'create_user_groups');

    $request = buildAdminFormRequest(StoreUserGroupRequest::class, ['institution_id' => $institution->id], $user);
    expect($request->authorize())->toBeTrue();
});

test('update user group request authorizes only users with update permission', function () {
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

test('findModelOrFail resolves the expected institution from the request identifier', function () {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $this->grantPermission($actor, $institution, 'edit_institutions');

    $request = buildAdminFormRequest(InstitutionIdRequest::class, ['id' => $institution->id], $actor);

    // Wire up the validator so validated() is available, matching real request lifecycle.
    $validator = \Illuminate\Support\Facades\Validator::make(
        $request->validationData(),
        $request->rules(),
    );
    $request->setValidator($validator);

    expect($request->institution()->id)->toBe($institution->id);
});
