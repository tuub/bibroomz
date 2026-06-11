<?php

use App\Http\Controllers\Admin\UserGroupController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Route;

covers(
    Authenticate::class,
    RedirectIfAuthenticated::class
);

test('login route uses login throttle middleware', function (): void {
    /** @var Illuminate\Routing\Route $route */
    $route = Route::getRoutes()->getByName('login');

    expect($route->gatherMiddleware())->toContain('throttle:login');
});

test('admin user group routes resolve to the correct controller', function (): void {
    $routeMap = [
        'admin.user_group.index' => 'getUserGroups',
        'admin.user_group.create' => 'createUserGroup',
        'admin.user_group.edit' => 'editUserGroup',
        'admin.user_group.store' => 'storeUserGroup',
        'admin.user_group.update' => 'updateUserGroup',
        'admin.user_group.delete' => 'deleteUserGroup',
        'admin.user_group.import' => 'importForm',
        'admin.user_group.users' => 'getUsers',
        'admin.user_group.users.import' => 'importUsers',
        'admin.user_group.users.remove' => 'removeUsers',
    ];

    foreach ($routeMap as $name => $method) {
        /** @var Illuminate\Routing\Route $route */
        $route = Route::getRoutes()->getByName($name);
        expect($route->getActionName())->toBe(UserGroupController::class.'@'.$method);
    }
});

test('admin resource group create route uses the correctly spelled institution path', function (): void {
    /** @var Illuminate\Routing\Route $route */
    $route = Route::getRoutes()->getByName('admin.resource_group.create');

    expect($route->uri())->toBe('admin/institution/{institution_id}/resource_group/create');
});
