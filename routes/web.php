<?php

use App\Http\Controllers\HappeningController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminController;

use App\Http\Controllers\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\ClosingController as AdminClosingController;
use App\Http\Controllers\Admin\HappeningController as AdminHappeningController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\StatisticController as AdminStatisticController;

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
        ->name('happening.add');
    Route::delete('/happening/delete/{id}', [HappeningController::class, 'deleteHappening'])
        ->name('happening.delete');

    // API calls
    Route::post('/{slug}/resource/{id}/time_slots', [ResourceController::class, 'getFormBusinessHours'])
        ->name('resource.business_hours.form');

    /* Admins */

    /* Dashboard */
    Route::get('/admin/dashboard', [AdminController::class, 'getDashboard'])
        ->name('admin.dashboard');

    /* Happenings */
    Route::get('/admin/happenings', [AdminHappeningController::class, 'getHappenings'])
        ->name('admin.happening.index');
    Route::get('/admin/happening/create', [AdminHappeningController::class, 'createHappening'])
        ->name('admin.happening.create');
    Route::get('/admin/happening/{id}/edit', [AdminHappeningController::class, 'editHappening'])
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

    /* Institution Special */
    Route::get('/admin/institution/{id}/settings', [AdminSettingController::class, 'getSettings'])
        ->name('admin.setting.index');
    // Route::get('/admin/form/institutions', [AdminInstitutionController::class, 'getFormInstitutions'])
    //     ->name('admin.institution.form');

    /* Resources */
    Route::get('/admin/resources', [AdminResourceController::class, 'getResources'])
        ->name('admin.resource.index');
    Route::get('/admin/resource/create', [AdminResourceController::class, 'createResource'])
        ->name('admin.resource.create');
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

    // /* Resource Special */
    // Route::get('/admin/form/resources', [AdminResourceController::class, 'getFormResources'])
    //     ->name('admin.resource.form');

    /* Closings */
    Route::get('/admin/{closable_type}/{closable_id}/closings', [AdminClosingController::class, 'getClosings'])
        ->name('admin.closing.index');
    Route::get('/admin/closing/{closable_type}/{closable_id}/create', [AdminClosingController::class, 'createClosing'])
        ->name('admin.closing.create');
    Route::get('/admin/closing/edit/{id}', [AdminClosingController::class, 'editClosing'])
        ->name('admin.closing.edit');
    Route::post('/admin/closing/store', [AdminClosingController::class, 'storeClosing'])
        ->name('admin.closing.store');
    Route::post('/admin/closing/update', [AdminClosingController::class, 'updateClosing'])
        ->name('admin.closing.update');
    Route::post('/admin/closing/delete', [AdminClosingController::class, 'deleteClosing'])
        ->name('admin.closing.delete');

    /* Settings */
    Route::get('/admin/setting/edit/{id}', [AdminSettingController::class, 'editSetting'])
        ->name('admin.setting.edit');
    Route::post('/admin/setting/update', [AdminSettingController::class, 'updateSetting'])
        ->name('admin.setting.update');

    /* Stats */
    Route::get('/admin/stats', [AdminStatisticController::class, 'getStats'])
        ->name('admin.statistic.index');

    /* Users */
    Route::get('/admin/users', [AdminUserController::class, 'getUsers'])
        ->name('admin.user.index');
    Route::get('/admin/user/edit/{id}', [AdminUserController::class, 'editUser'])
        ->name('admin.user.edit');
    Route::post('/admin/user/delete/{id}', [AdminUserController::class, 'deleteUser'])
        ->name('admin.user.delete');
    Route::post('/admin/user/update', [AdminUserController::class, 'updateUser'])
        ->name('admin.user.update');

    // /* User Special */
    // Route::get('/admin/form/users', [AdminUserController::class, 'getFormUsers'])
    //     ->name('admin.user.form');

    /* Roles and Permissions */
    Route::get('/admin/roles', [RoleController::class, 'getRoles'])
        ->name('admin.role.index');
    Route::get('/admin/role/create', [RoleController::class, 'createRole'])
        ->name('admin.role.create');
    Route::post('/admin/role/store', [RoleController::class, 'storeRole'])
        ->name('admin.role.store');
    Route::get('/admin/role/edit/{role}', [RoleController::class, 'editRole'])
        ->name('admin.role.edit');
    Route::post('/admin/role/update/{role}', [RoleController::class, 'updateRole'])
        ->name('admin.role.update');
    Route::post('/admin/role/delete/{role}', [RoleController::class, 'deleteRole'])
        ->name('admin.role.delete');

    Route::get('/admin/permissions', [PermissionController::class, 'getPermissions'])
        ->name('admin.permission.index');
    Route::get('/admin/permission/create', [PermissionController::class, 'createPermission'])
        ->name('admin.permission.create');
    Route::post('/admin/permission/store', [PermissionController::class, 'storePermission'])
        ->name('admin.permission.store');
    Route::get('/admin/permission/edit/{permission}', [PermissionController::class, 'editPermission'])
        ->name('admin.permission.edit');
    Route::post('/admin/permission/update/{permission}', [PermissionController::class, 'updatePermission'])
        ->name('admin.permission.update');
    Route::post('/admin/permission/delete/{permission}', [PermissionController::class, 'deletePermission'])
        ->name('admin.permission.delete');
});

// Institution Home
Route::get('/{slug}', [HomeController::class, 'getInstitutionalHome'])
    ->name('home');

// Institution Terminal View
Route::get('/{slug}/terminal-view', [HomeController::class, 'getTerminalView'])
    ->name('terminal_view');

// API calls
Route::get('/{slug}/resources', [ResourceController::class, 'getResources'])
    ->name('resources.get');
Route::get('/{slug}/happenings', [HappeningController::class, 'getHappenings'])
    ->name('happenings.get');
