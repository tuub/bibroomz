<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum', 'can:admin'])->group(function (): void {
    Route::get('/admin/user/users', [AdminUserController::class, 'getFormUsers'])
        ->name('api.admin.user.users');
});
