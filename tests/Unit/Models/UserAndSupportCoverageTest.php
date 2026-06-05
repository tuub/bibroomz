<?php

covers(
    App\Models\User::class,
    App\Models\UserGroup::class,
    App\Models\Setting::class,
    App\Models\MailContent::class,
    App\Models\MailType::class,
    App\Traits\HasTranslations::class
);

use App\Library\Utility;
use App\Models\Closing;
use App\Models\Happening;
use App\Models\Institution;
use App\Models\InstitutionUserRole;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\Permission;
use App\Models\Resource;
use App\Models\ResourceGroup;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;

uses(InteractsWithPermissions::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedPermissions();
    Carbon::setTestNow(Carbon::parse('2026-06-03 10:00:00', 'UTC'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-03 10:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('user helpers institution helpers and supporting models cover permission and relation branches', function () {
    $institution = Institution::factory()->create(['is_active' => true]);
    $resourceGroup = ResourceGroup::factory()->create(['institution_id' => $institution->id]);
    $resource = Resource::factory()->create(['resource_group_id' => $resourceGroup->id]);

    $owner = User::factory()->create(['name' => 'owner']);
    $partner = User::factory()->create(['name' => 'partner']);
    $admin = User::factory()->create(['name' => 'admin', 'is_admin' => true, 'is_system_user' => true]);
    $editor = User::factory()->create(['name' => 'editor']);

    $this->grantPermission($editor, $institution, 'view_institution');
    $this->grantPermission($editor, $institution, 'edit_institution');
    $this->grantPermission($editor, $institution, 'create_resources');
    $this->grantPermission($editor, $institution, 'create_resource_groups');
    $this->grantPermission($editor, $institution, 'create_user_groups');
    $this->grantPermission($editor, $institution, 'view_mails');

    $group = UserGroup::create([
        'title' => Utility::getTranslatable('Members'),
        'institution_id' => $institution->id,
    ]);
    $resourceGroup->user_groups()->attach($group);
    $owner->user_groups()->attach($group);

    $first = Happening::create([
        'user_id_01' => $owner->id,
        'user_id_02' => $partner->id,
        'resource_id' => $resource->id,
        'is_verified' => true,
        'verifier' => 'verifier',
        'start' => CarbonImmutable::now()->addHour(),
        'end' => CarbonImmutable::now()->addHours(2),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => CarbonImmutable::now(),
        'label' => Utility::getTranslatable('Study'),
    ]);

    $second = Happening::create([
        'user_id_01' => $partner->id,
        'user_id_02' => $owner->id,
        'resource_id' => $resource->id,
        'is_verified' => false,
        'verifier' => 'verifier',
        'start' => CarbonImmutable::now()->addHours(3),
        'end' => CarbonImmutable::now()->addHours(4),
        'reserved_at' => CarbonImmutable::now(),
        'verified_at' => null,
        'label' => Utility::getTranslatable('Group Work'),
    ]);

    expect($owner->isAdmin())->toBeFalse()
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isSystemUser())->toBeTrue()
        ->and($owner->getHappenings()->pluck('id')->all())->toContain($first->id, $second->id)
        ->and($owner->getOtherUserHappeningsForResourceGroup($resourceGroup, $first)
            ->pluck('id')->all())->toContain($second->id)
        ->and($owner->getOtherUserHappeningsForResourceGroup($resourceGroup, $first)
            ->pluck('id')->all())->not->toContain($first->id)
        ->and($owner->isHavingConcurrentHappening(
            CarbonImmutable::now()->addHours(3)->addMinutes(15),
            CarbonImmutable::now()->addHours(3)->addMinutes(45),
            $first
        ))->toBeTrue()
        ->and($owner->isHavingConcurrentHappening(
            CarbonImmutable::now()->addHours(5),
            CarbonImmutable::now()->addHours(6),
            $first
        ))->toBeFalse();

    expect($admin->getPermissions(['view_mails'])->flatten()->unique()->all())->toBe(['view_mails'])
        ->and($admin->hasPermission('view_mails', $institution))->toBeTrue()
        ->and($editor->hasPermission('view_mails', $institution))->toBeTrue()
        ->and($owner->hasPermission('view_mails', $institution))->toBeFalse();

    cache()->forget('user_activity_' . $owner->id);
    $owner->update(['is_logged_in' => false]);
    expect($owner->isLoggedIn())->toBeFalse();
    $owner->update(['is_logged_in' => true]);
    cache()->put('user_activity_' . $owner->id, now(), now()->addMinutes(10));
    expect($owner->isLoggedIn())->toBeTrue();

    expect($institution->isViewableByUser($editor))->toBeTrue()
        ->and($institution->isEditableByUser($editor))->toBeTrue()
        ->and($institution->isUserAbleToCreateResource($editor))->toBeTrue()
        ->and($institution->isUserAbleToCreateResourceGroup($editor))->toBeTrue()
        ->and($institution->isUserAbleToCreateUserGroup($editor))->toBeTrue()
        ->and($resourceGroup->isAllowedUser($owner))->toBeTrue()
        ->and($resourceGroup->isViewableByUser($owner))->toBeFalse()
        ->and($group->isViewableByUser($owner))->toBeFalse();

    $mailType = MailType::create([
        'key' => 'closing_created',
        'description' => 'Closing created',
    ]);
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => Utility::getTranslatable('Subject'),
        'title' => Utility::getTranslatable('Title'),
        'salutation' => Utility::getTranslatable('Hello'),
        'intro' => Utility::getTranslatable('Intro'),
        'outro' => Utility::getTranslatable('Outro'),
        'farewell' => Utility::getTranslatable('Bye'),
        'action_uri' => 'https://example.org',
        'action_uri_label' => 'Open',
        'is_active' => true,
    ]);

    expect($mailType->mail_contents->pluck('id')->all())->toContain($mailContent->id)
        ->and($mailContent->isViewableByUser($editor))->toBeTrue();

    $permission = Permission::firstWhere('key', 'view_mails');
    $role = Role::create([
        'name' => Utility::getTranslatable('Mail Viewer'),
        'description' => Utility::getTranslatable('Can view mail content'),
    ]);
    $role->permissions()->attach($permission);

    expect($role->hasPermission('view_mails'))->toBeTrue()
        ->and($role->getPermissionKeys())->toContain('view_mails')
        ->and($role->getPermissionKeys(['view_mails', 'edit_mails']))->toBe(['view_mails']);

    $pivot = new InstitutionUserRole();
    $pivot->institution_id = $institution->id;
    $pivot->setRelation('institution', $institution);
    $pivot->setRelation('role', $role);
    $role->setRelation('pivot', $pivot);

    expect($role->hasPermission('view_mails', $institution))->toBeTrue()
        ->and($pivot->hasPermission('view_mails', $institution))->toBeTrue();

    expect(Closing::getClosableModel('institution'))->toBeInstanceOf(Institution::class)
        ->and(Closing::getClosableModel('resource'))->toBeInstanceOf(Resource::class);

    $first->delete();
    expect(Happening::query()->find($second->id))->not->toBeNull();

    $owner->delete();
    expect(Happening::query()->find($first->id))->toBeNull()
        ->and(Happening::query()->find($second->id))->toBeNull();
});
