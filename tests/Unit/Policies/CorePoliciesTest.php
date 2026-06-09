<?php

use App\Library\Utility;
use App\Models\Closing;
use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Policies\ClosingPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\MailContentPolicy;
use App\Policies\ResourcePolicy;
use App\Policies\RolePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

covers(
    ClosingPolicy::class,
    InstitutionPolicy::class,
    MailContentPolicy::class,
    ResourcePolicy::class,
    RolePolicy::class,
    SettingPolicy::class
);

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(fn () => $this->seedPermissions());

test('institution policy honors global and scoped permissions', function (): void {
    $institution = Institution::factory()->create();
    $user = User::factory()->create();
    $policy = new InstitutionPolicy;

    $this->grantPermission($user, $institution, 'view_institution');
    $this->grantPermission($user, $institution, 'edit_institution');
    $this->grantPermission($user, $institution, 'delete_institution');
    $this->grantPermission($user, $institution, 'create_institutions');

    expect($policy->view($user, $institution))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user, $institution))->toBeTrue()
        ->and($policy->edit($user, $institution))->toBeTrue()
        ->and($policy->delete($user, $institution))->toBeTrue();
});

test('resource policy grants all resource operations in the same institution', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $user = User::factory()->create();
    $policy = new ResourcePolicy;

    $this->grantPermission($user, $institution, 'view_resources');
    $this->grantPermission($user, $institution, 'create_resources');
    $this->grantPermission($user, $institution, 'edit_resources');
    $this->grantPermission($user, $institution, 'delete_resources');

    expect($policy->view($user, $resource))->toBeTrue()
        ->and($policy->create($user, $institution))->toBeTrue()
        ->and($policy->update($user, $resource))->toBeTrue()
        ->and($policy->edit($user, $resource))->toBeTrue()
        ->and($policy->delete($user, $resource))->toBeTrue()
        ->and($policy->clone($user, $resource))->toBeTrue();
});

test('role policy uses the matching role permissions', function (): void {
    $institution = Institution::factory()->create();
    $role = Role::create(['name' => Utility::getTranslatable('Editor')]);
    $user = User::factory()->create();
    $policy = new RolePolicy;

    $this->grantPermission($user, $institution, 'view_roles');
    $this->grantPermission($user, $institution, 'create_roles');
    $this->grantPermission($user, $institution, 'edit_roles');
    $this->grantPermission($user, $institution, 'delete_roles');

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->update($user))->toBeTrue()
        ->and($policy->edit($user, $role))->toBeTrue()
        ->and($policy->delete($user))->toBeTrue();
});

test('setting policy resolves permissions from the related institution', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $setting = new Setting([
        'key' => 'timezone',
        'value' => 'Europe/Berlin',
        'institution_id' => $institution->id,
    ]);
    $setting->setRelation('settingable', $institution);

    $user = User::factory()->create();
    $policy = new SettingPolicy;

    $this->grantPermission($user, $institution, 'view_settings');
    $this->grantPermission($user, $institution, 'edit_settings');

    expect($policy->viewAny($user, $institution))->toBeTrue()
        ->and($policy->viewAny($user, $resourceGroup))->toBeTrue()
        ->and($policy->view($user, $setting))->toBeTrue()
        ->and($policy->update($user, $setting))->toBeTrue()
        ->and($policy->edit($user, $setting))->toBeTrue();
});

test('user policy blocks admin targets without the dedicated permission', function (): void {
    $institution = Institution::factory()->create();
    $actor = User::factory()->create();
    $adminTarget = User::factory()->create(['is_admin' => true]);
    $normalTarget = User::factory()->create();
    $policy = new UserPolicy;

    $this->grantPermission($actor, $institution, 'view_users');
    $this->grantPermission($actor, $institution, 'create_users');
    $this->grantPermission($actor, $institution, 'edit_users');
    $this->grantPermission($actor, $institution, 'delete_users');

    expect($policy->viewAny($actor))->toBeTrue()
        ->and($policy->create($actor))->toBeTrue()
        ->and($policy->update($actor, $normalTarget))->toBeTrue()
        ->and($policy->delete($actor, $normalTarget))->toBeTrue()
        ->and($policy->update($actor, $adminTarget))->toBeFalse()
        ->and($policy->delete($actor, $adminTarget))->toBeFalse()
        ->and($policy->ban($actor, $normalTarget))->toBeTrue()
        ->and($policy->unban($actor, $normalTarget))->toBeTrue();

    $this->grantPermission($actor, $institution, 'edit_admin_users');
    $this->grantPermission($actor, $institution, 'delete_admin_users');

    expect($policy->edit($actor, $adminTarget))->toBeTrue()
        ->and($policy->delete($actor, $adminTarget))->toBeTrue();
});

test('mail content policy is scoped to institution permissions', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::create([
        'key' => 'booking_created',
        'description' => 'Booking created',
    ]);
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Subject'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hi'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $policy = new MailContentPolicy;

    $this->grantPermission($user, $institution, 'view_mails');
    $this->grantPermission($user, $institution, 'create_mails');
    $this->grantPermission($user, $institution, 'edit_mails');
    $this->grantPermission($user, $institution, 'delete_mails');

    expect($policy->viewAny($user, $institution))->toBeTrue()
        ->and($policy->view($user, $mailContent))->toBeTrue()
        ->and($policy->create($user, $institution))->toBeTrue()
        ->and($policy->update($user, $mailContent))->toBeTrue()
        ->and($policy->edit($user, $mailContent))->toBeTrue()
        ->and($policy->delete($user, $mailContent))->toBeTrue();
});

test('closing policy handles institution and nested closables', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);
    $resource->setRelation('institution', $institution);

    $closing = new Closing([
        'start' => now()->subHour(),
        'end' => now()->addHour(),
    ]);
    $closing->setRelation('closable', $resource);

    $user = User::factory()->create();
    $policy = new ClosingPolicy;

    $this->grantPermission($user, $institution, 'view_closings');
    $this->grantPermission($user, $institution, 'create_closings');
    $this->grantPermission($user, $institution, 'edit_closings');
    $this->grantPermission($user, $institution, 'delete_closings');

    expect($policy->viewAny($user, $institution))->toBeTrue()
        ->and($policy->viewAny($user, $resource))->toBeTrue()
        ->and($policy->create($user, $institution))->toBeTrue()
        ->and($policy->create($user, $resource))->toBeTrue()
        ->and($policy->update($user, $closing))->toBeTrue()
        ->and($policy->edit($user, $closing))->toBeTrue()
        ->and($policy->delete($user, $closing))->toBeTrue();
});
