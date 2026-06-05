<?php

covers(
    App\Http\Middleware\Authenticate::class,
    App\Http\Middleware\RedirectIfAuthenticated::class
);

use App\Http\Controllers\Admin\UserGroupController;
use Illuminate\Support\Facades\Route;

test('login route uses login throttle middleware', function () {
    $route = Route::getRoutes()->getByName('login');

    expect($route->gatherMiddleware())->toContain('throttle:login');
});

test('admin user group routes resolve to the correct controller', function () {
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
        $route = Route::getRoutes()->getByName($name);
        expect($route->getActionName())->toBe(UserGroupController::class . '@' . $method);
    }
});
