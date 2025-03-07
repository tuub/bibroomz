<?php

use App\Http\Controllers\HappeningController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Admin\ResourceGroupController as AdminResourceGroupController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\ClosingController as AdminClosingController;
use App\Http\Controllers\Admin\HappeningController as AdminHappeningController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use App\Http\Controllers\Admin\PermissionGroupController as AdminPermissionGroupController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\MailController as AdminMailController;
use App\Http\Controllers\Admin\UserGroupController as AdminUSerGroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'getStart'])
    ->name('start');
Route::get('/privacy-statement', [HomeController::class, 'getPrivacyStatement'])
    ->name('privacy_statement');
Route::get('/site-credits', [HomeController::class, 'getSiteCredits'])
    ->name('site_credits');
Route::post('/switch-lang', [HomeController::class, 'switchLanguage'])
    ->name('switch_lang');

Route::post('/check', [LoginController::class, 'check'])
    ->name('check');
Route::post('/login', [LoginController::class, 'login'])
    ->name('login');
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth:sanctum')->group(function () {
    /* User actions */
    Route::get('/my/happenings', [UserController::class, 'getUserHappenings'])
        ->name('user.happenings.get');
    Route::post('/happening/add', [HappeningController::class, 'addHappening'])
        ->name('happening.add');
    Route::post('/happening/verify/{id}', [HappeningController::class, 'verifyHappening'])
        ->name('happening.verify');
    Route::post('/happening/update/{id}', [HappeningController::class, 'updateHappening'])
        ->name('happening.update');
    Route::delete('/happening/delete/{id}', [HappeningController::class, 'deleteHappening'])
        ->name('happening.delete');

    // API calls
    Route::post(
        '/{institution_slug}/{resource_group_slug}/resource/{id}/time_slots',
        [ResourceController::class, 'getTimeSlots']
    )->name('resource.time_slots');

    /* Admins */
    Route::middleware('can:view-admin-panel')->group(function () {
        /* Dashboard */
        Route::get('/admin/dashboard', [AdminController::class, 'getDashboard'])
            ->name('admin.dashboard');

        /* Happenings */
        Route::get('/admin/happenings', [AdminHappeningController::class, 'getHappenings'])
            ->name('admin.happening.index');
        Route::get('/admin/happening/create', [AdminHappeningController::class, 'createHappening'])
            ->name('admin.happening.create');
        Route::get('/admin/happening/edit/{id}', [AdminHappeningController::class, 'editHappening'])
            ->name('admin.happening.edit');
        Route::post('/admin/happening/store', [AdminHappeningController::class, 'storeHappening'])
            ->name('admin.happening.store');
        Route::post('/admin/happening/update', [AdminHappeningController::class, 'updateHappening'])
            ->name('admin.happening.update');
        Route::post('/admin/happening/delete', [AdminHappeningController::class, 'deleteHappening'])
            ->name('admin.happening.delete');

        /* Institutions */
        Route::get('/admin/institutions', [AdminInstitutionController::class, 'getInstitutions'])
            ->name('admin.institution.index');
        Route::post('/admin/institutions/order', [AdminInstitutionController::class, 'orderInstitutions'])
            ->name('admin.institution.order');
        Route::get('/admin/institution/create', [AdminInstitutionController::class, 'createInstitution'])
            ->name('admin.institution.create');
        Route::get('/admin/institution/{id}/edit', [AdminInstitutionController::class, 'editInstitution'])
            ->name('admin.institution.edit');
        Route::post('/admin/institution/store', [AdminInstitutionController::class, 'storeInstitution'])
            ->name('admin.institution.store');
        Route::post('/admin/institution/update', [AdminInstitutionController::class, 'updateInstitution'])
            ->name('admin.institution.update');
        Route::post('/admin/institution/delete', [AdminInstitutionController::class, 'deleteInstitution'])
            ->name('admin.institution.delete');

        /* Resource Groups */
        Route::get('/admin/resource_groups', [AdminResourceGroupController::class, 'getResourceGroups'])
            ->name('admin.resource_group.index');
        Route::get('/admin/resource_group/create', [AdminResourceGroupController::class, 'createResourceGroup'])
            ->name('admin.resource_group.create');
        Route::get('/admin/resource_group/{id}/edit', [AdminResourceGroupController::class, 'editResourceGroup'])
            ->name('admin.resource_group.edit');
        Route::post('/admin/resource_group/store', [AdminResourceGroupController::class, 'storeResourceGroup'])
            ->name('admin.resource_group.store');
        Route::post('/admin/resource_group/update', [AdminResourceGroupController::class, 'updateResourceGroup'])
            ->name('admin.resource_group.update');
        Route::post('/admin/resource_group/delete', [AdminResourceGroupController::class, 'deleteResourceGroup'])
            ->name('admin.resource_group.delete');

        /* Resources */
        Route::get(
            '/admin/resource_group/{resource_group_id}/resources',
            [AdminResourceController::class, 'getResources'],
        )->name('admin.resource.index');
        Route::get(
            '/admin/resource_group/{resource_group_id}/resource/create',
            [AdminResourceController::class, 'createResource'],
        )->name('admin.resource.create');
        Route::get('/admin/resource/{id}/edit', [AdminResourceController::class, 'editResource'])
            ->name('admin.resource.edit');
        Route::post('/admin/resource/store', [AdminResourceController::class, 'storeResource'])
            ->name('admin.resource.store');
        Route::post('/admin/resource/update', [AdminResourceController::class, 'updateResource'])
            ->name('admin.resource.update');
        Route::post('/admin/resource/delete', [AdminResourceController::class, 'deleteResource'])
            ->name('admin.resource.delete');
        Route::post('/admin/resource/clone', [AdminResourceController::class, 'cloneResource'])
            ->name('admin.resource.clone');

        /* Closings */
        Route::get('/admin/{closable_type}/{closable_id}/closings', [AdminClosingController::class, 'getClosings'])
            ->name('admin.closing.index');
        Route::get(
            '/admin/{closable_type}/{closable_id}/closing/create',
            [AdminClosingController::class, 'createClosing'],
        )->name('admin.closing.create');
        Route::get('/admin/closing/{id}/edit', [AdminClosingController::class, 'editClosing'])
            ->name('admin.closing.edit');
        Route::post('/admin/closing/store', [AdminClosingController::class, 'storeClosing'])
            ->name('admin.closing.store');
        Route::post('/admin/closing/update', [AdminClosingController::class, 'updateClosing'])
            ->name('admin.closing.update');
        Route::post('/admin/closing/delete', [AdminClosingController::class, 'deleteClosing'])
            ->name('admin.closing.delete');

        /* Settings */
        Route::get('/admin/{settingable_type}/{settingable_id}/settings', [
            AdminSettingController::class, 'getSettings'
        ])
            ->name('admin.setting.index');
        Route::get('/admin/setting/{id}/edit', [AdminSettingController::class, 'editSetting'])
            ->name('admin.setting.edit');
        Route::post('/admin/setting/update', [AdminSettingController::class, 'updateSetting'])
            ->name('admin.setting.update');

        /* Mails */
        Route::get('/admin/institution/{institution_id}/mails', [AdminMailController::class, 'getMails'])
            ->name('admin.mail.index');
        Route::get('/admin/institution/{institution_id}/mail/create', [AdminMailController::class, 'createMail'])
            ->name('admin.mail.create');
        Route::post('/admin/mail/store', [AdminMailController::class, 'storeMail'])
            ->name('admin.mail.store');
        Route::get('/admin/mail/{id}/edit', [AdminMailController::class, 'editMail'])
            ->name('admin.mail.edit');
        Route::post('/admin/mail/update', [AdminMailController::class, 'updateMail'])
            ->name('admin.mail.update');
        Route::post('/admin/mail/delete', [AdminMailController::class, 'deleteMail'])
            ->name('admin.mail.delete');

        /* Users */
        Route::get('/admin/users', [AdminUserController::class, 'getUsers'])
            ->name('admin.user.index');
        Route::get('/admin/user/create', [AdminUserController::class, 'createUser'])
            ->name('admin.user.create');
        Route::post('/admin/user/store', [AdminUserController::class, 'storeUser'])
            ->name('admin.user.store');
        Route::get('/admin/user/{id}/edit', [AdminUserController::class, 'editUser'])
            ->name('admin.user.edit');
        Route::post('/admin/user/update', [AdminUserController::class, 'updateUser'])
            ->name('admin.user.update');
        Route::post('/admin/user/delete', [AdminUserController::class, 'deleteUser'])
            ->name('admin.user.delete');
        Route::post('/admin/user/ban', [AdminUserController::class, 'banUser'])
            ->name('admin.user.ban');
        Route::post('/admin/user/unban', [AdminUserController::class, 'unbanUser'])
            ->name('admin.user.unban');

        /* Roles */
        Route::get('/admin/roles', [AdminRoleController::class, 'getRoles'])
            ->name('admin.role.index');
        Route::get('/admin/role/create', [AdminRoleController::class, 'createRole'])
            ->name('admin.role.create');
        Route::post('/admin/role/store', [AdminRoleController::class, 'storeRole'])
            ->name('admin.role.store');
        Route::get('/admin/role/{id}/edit', [AdminRoleController::class, 'editRole'])
            ->name('admin.role.edit');
        Route::post('/admin/role/update', [AdminRoleController::class, 'updateRole'])
            ->name('admin.role.update');
        Route::post('/admin/role/delete', [AdminRoleController::class, 'deleteRole'])
            ->name('admin.role.delete');

        /* Permissions */
        Route::get('/admin/permissions', [AdminPermissionController::class, 'getPermissions'])
            ->name('admin.permission.index');
        Route::get('/admin/permission/{id}/edit', [AdminPermissionController::class, 'editPermission'])
            ->name('admin.permission.edit');
        Route::post('/admin/permission/update', [AdminPermissionController::class, 'updatePermission'])
            ->name('admin.permission.update');

        /* Permission Groups */
        Route::get('/admin/permission_groups', [AdminPermissionGroupController::class, 'getPermissionGroups'])
            ->name('admin.permission_group.index');
        Route::get('/admin/permission_group/{id}/edit', [AdminPermissionGroupController::class, 'editPermissionGroup'])
            ->name('admin.permission_group.edit');
        Route::post('/admin/permission_group/update', [AdminPermissionGroupController::class, 'updatePermissionGroup'])
            ->name('admin.permission_group.update');

        /* User Groups */
        Route::get('/admin/user_groups', [AdminUserGroupController::class, 'getUserGroups'])
            ->name('admin.user_group.index');
        Route::get('/admin/user_group/create', [AdminUserGroupController::class, 'createUserGroup'])
            ->name('admin.user_group.create');
        Route::get('/admin/user_group/{id}/edit', [AdminUserGroupController::class, 'editUserGroup'])
            ->name('admin.user_group.edit');
        Route::post('/admin/user_group/store', [AdminUserGroupController::class, 'storeUserGroup'])
            ->name('admin.user_group.store');
        Route::post('/admin/user_group/update', [AdminUserGroupController::class, 'updateUserGroup'])
            ->name('admin.user_group.update');
        Route::post('/admin/user_group/delete', [AdminUserGroupController::class, 'deleteUserGroup'])
            ->name('admin.user_group.delete');
        Route::get('/admin/user_group/import', [AdminUserGroupController::class, 'importForm'])
            ->name('admin.user_group.import');
        Route::post('/admin/user_group/users/import', [AdminUserGroupController::class, 'importUsers'])
            ->name('admin.user_group.users.import');

        Route::get('/admin', [AdminController::class, 'getDashboard']);
    });
});

// Institution Home
Route::get('/{institution_slug}/{resource_group_slug}/home', [HomeController::class, 'getInstitutionalHome'])
    ->name('home');

// Institution Terminal View
Route::get('/{institution_slug}/{resource_group_slug}/terminal-view', [HomeController::class, 'getTerminalView'])
    ->name('terminal_view');

// API calls
Route::get('/{institution_slug}/{resource_group_slug}/resources', [ResourceController::class, 'getResources'])
    ->name('resources.get');
Route::get('/{institution_slug}/{resource_group_slug}/happenings', [HappeningController::class, 'getHappenings'])
    ->name('happenings.get');
