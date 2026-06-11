<?php

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\MailType;
use App\Models\ResourceGroup;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserGroupUser;
use App\Models\WeekDay;
use App\Services\Admin\SettingableResolver;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\WeekDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

covers(
    Setting::class,
    UserGroup::class,
    UserGroupUser::class,
    WeekDay::class,
    MailContent::class,
    MailType::class,
    SettingableResolver::class,
);

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, WeekDaySeeder::class]);
});

test('setting stores and retrieves values', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'active_test',
        'value' => 'test_value',
    ]);

    // Test that setting was created and can be retrieved
    expect($setting->key)->toBe('active_test');
    expect($setting->value)->toBe('test_value');
});

test('user group user pivot has dates', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create(['title' => ['en' => 'Group'], 'institution_id' => $institution->id]);
    $user = User::factory()->create();

    $userGroup->users()->attach($user->id, [
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDay(),
    ]);

    $userGroupRelation = $user->user_groups->first();
    expect($userGroupRelation)->not->toBeNull();
    if ($userGroupRelation) {
        /** @var UserGroupUser $pivot */
        $pivot = $userGroupRelation->pivot;
        expect($pivot)->toBeInstanceOf(UserGroupUser::class);
        expect($pivot->valid_from)->not->toBeNull();
    }
});

test('week day business hours and institutions relations exist', function (): void {
    $weekDay = WeekDay::first();
    expect($weekDay)->not->toBeNull();

    if ($weekDay) {
        // These relations should exist even if empty
        expect($weekDay->business_hours())->not->toBeNull();
        expect($weekDay->institutions())->not->toBeNull();
    }
});

test('mail type has mail contents relation', function (): void {
    $mailType = MailType::first() ?? MailType::create(['key' => 'test', 'description' => 'Test']);

    expect($mailType->mail_contents())->not->toBeNull();
});

test('mail content is active boolean cast', function (): void {
    $institution = Institution::factory()->create();
    $mailType = MailType::first() ?? MailType::create(['key' => 'test', 'description' => 'Test']);

    $mail = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => 'Test',
        'is_active' => true,
    ]);

    expect($mail->is_active)->toBeTrue();
});

test('settingable resolver resolves institution', function (): void {
    $resolver = app(SettingableResolver::class);
    $institution = Institution::factory()->create();

    $result = $resolver->resolve(Institution::class, $institution->id);

    expect($result)->toBeInstanceOf(Institution::class);
    expect($result->id)->toBe($institution->id);
});

test('settingable resolver returns type for model', function (): void {
    $resolver = app(SettingableResolver::class);
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    expect($resolver->typeForModel($institution))->toBe('institution');
    expect($resolver->typeForModel($resourceGroup))->toBe('resource_group');
});

test('admin user can view all resources', function (): void {
    $this->seed(PermissionSeeder::class);
    $institution = Institution::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $userGroup = UserGroup::create(['title' => ['en' => 'Test'], 'institution_id' => $institution->id]);

    expect($userGroup->isViewableByUser($admin))->toBeTrue();
});

test('mail content is viewable by authorized admin', function (): void {
    $this->seed([PermissionSeeder::class]);
    $institution = Institution::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    $mailType = MailType::create(['key' => 'test', 'description' => 'Test Mail']);
    $mailContent = MailContent::create([
        'institution_id' => $institution->id,
        'mail_type_id' => $mailType->id,
        'subject' => ['en' => 'Test Subject'],
    ]);

    expect($mailContent->isViewableByUser($admin))->toBeTrue();
});

test('setting get institution returns institution from settingable', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test',
        'value' => 'test',
    ]);

    expect($setting->getInstitution()->id)->toBe($institution->id);
});

test('setting get institution with institution relation works', function (): void {
    $institution = Institution::factory()->create();

    // Test with Institution type
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test_inst',
        'value' => 'value',
    ]);

    expect($setting->getInstitution())->toBeInstanceOf(Institution::class);
});

test('setting get institution with resource group relation works', function (): void {
    $institution = Institution::factory()->create();
    $resourceGroup = ResourceGroup::factory()->for($institution, 'institution')->create();

    // Test with ResourceGroup type
    $setting = Setting::create([
        'settingable_type' => ResourceGroup::class,
        'settingable_id' => $resourceGroup->id,
        'key' => 'test_rg',
        'value' => 'value',
    ]);

    expect($setting->getInstitution())->toBeInstanceOf(Institution::class);
});

test('all model relations can be accessed', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::create(['title' => ['en' => 'Test'], 'institution_id' => $institution->id]);
    $user = User::factory()->create();

    // Test relation accessors
    expect($userGroup->users())->not->toBeNull();
    expect($userGroup->resource_groups())->not->toBeNull();

    // Attach for pivot testing
    $userGroup->users()->attach($user->id);
    $userGroupRelation = $user->user_groups->first();
    expect($userGroupRelation)->not->toBeNull();

    if ($userGroupRelation) {
        /** @var UserGroupUser $pivot */
        $pivot = $userGroupRelation->pivot;
        expect($pivot->user())->not->toBeNull();
        expect($pivot->user_group())->not->toBeNull();
    }

    // Test setting relations
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test',
        'value' => 'test',
    ]);
    expect($setting->settingable())->not->toBeNull();
});

test('setting get settingable model throws on invalid type', function (): void {
    expect(fn (): Institution|ResourceGroup => Setting::getSettingableModel('invalid_type'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported settingable type');
});

test('setting get institution with valid institution settingable', function (): void {
    $institution = Institution::factory()->create();
    $setting = Setting::create([
        'settingable_type' => Institution::class,
        'settingable_id' => $institution->id,
        'key' => 'test',
        'value' => 'value',
    ]);

    $result = $setting->getInstitution();
    expect($result)->toBeInstanceOf(Institution::class);
    expect($result->id)->toBe($institution->id);
});

test('setting get settingable model returns resource group instance', function (): void {
    $model = Setting::getSettingableModel('resource_group');
    expect($model)->toBeInstanceOf(ResourceGroup::class);
});
