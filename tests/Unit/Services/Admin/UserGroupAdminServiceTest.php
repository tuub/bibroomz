<?php

declare(strict_types=1);

use App\Models\Institution;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Admin\UserGroupAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

covers(UserGroupAdminService::class);

uses(RefreshDatabase::class);

test('getIndexData returns user_groups key', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $service = app(UserGroupAdminService::class);
    $data = $service->getIndexData($user);

    expect($data)->toHaveKey('user_groups');
});

test('store creates a user group', function (): void {
    $institution = Institution::factory()->create();
    $service = app(UserGroupAdminService::class);

    $ug = $service->store([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Students', 'de' => 'Studierende'],
    ]);

    expect($ug)->toBeInstanceOf(UserGroup::class)
        ->and($ug->institution_id)->toBe($institution->id);
});

test('getCreateFormData returns institutions and languages keys', function (): void {
    $user = User::factory()->create(['is_admin' => true]);
    $service = app(UserGroupAdminService::class);

    $data = $service->getCreateFormData($user);

    expect($data)->toHaveKey('institutions')
        ->and($data)->toHaveKey('languages');
});

test('getEditFormData returns user_group and languages keys', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $service = app(UserGroupAdminService::class);

    $data = $service->getEditFormData($userGroup);

    expect($data)->toHaveKey('user_group')
        ->and($data)->toHaveKey('languages')
        ->and($data['user_group'])->toBeInstanceOf(UserGroup::class)
        ->and($data['user_group']->id)->toBe($userGroup->id);
});

test('getImportFormData returns user_group key', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $service = app(UserGroupAdminService::class);

    $data = $service->getImportFormData($userGroup);

    expect($data)->toHaveKey('user_group')
        ->and($data['user_group']->id)->toBe($userGroup->id);
});

test('getUsersData returns user_group and users keys', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $service = app(UserGroupAdminService::class);

    $data = $service->getUsersData($userGroup);

    expect($data)->toHaveKey('user_group')
        ->and($data)->toHaveKey('users');
});

test('update updates user group and returns updated instance', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create([
        'title' => ['en' => 'Original'],
    ]);
    $service = app(UserGroupAdminService::class);

    $updated = $service->update($userGroup, ['title' => ['en' => 'Updated']]);

    expect($updated)->toBeInstanceOf(UserGroup::class)
        ->and($updated->getTranslation('title', 'en'))->toBe('Updated');
});

test('delete removes user group from database', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $id = $userGroup->id;
    $service = app(UserGroupAdminService::class);

    $service->delete($userGroup);

    expect(UserGroup::find($id))->toBeNull();
});

test('removeUsers detaches users from user group', function (): void {
    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $user = User::factory()->create();
    $userGroup->users()->attach($user->id);
    $service = app(UserGroupAdminService::class);

    $service->removeUsers($userGroup, [$user->id]);

    /** @var UserGroup $freshGroup */
    $freshGroup = $userGroup->fresh();
    expect($freshGroup->users()->count())->toBe(0);
});

test('store logs created action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $service = app(UserGroupAdminService::class);

    $service->store([
        'institution_id' => $institution->id,
        'title' => ['en' => 'Logged Group'],
    ]);
});

test('update logs updated action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create([
        'title' => ['en' => 'Before'],
    ]);
    $service = app(UserGroupAdminService::class);

    $service->update($userGroup, ['title' => ['en' => 'After']]);
});

test('delete logs deleted action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $service = app(UserGroupAdminService::class);

    $service->delete($userGroup);
});

test('importUsers logs import action via admin channel', function (): void {
    Log::shouldReceive('channel')->once()->with('admin')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $institution = Institution::factory()->create();
    $userGroup = UserGroup::factory()->for($institution, 'institution')->create();
    $service = app(UserGroupAdminService::class);

    $service->importUsers($userGroup, ['users' => []]);
});
