<?php

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\TubAuthController;
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

Route::get('/', function () {
    return view('app');
});

Route::get('/user', function () {
    return 'LOGGED IN';
});

Route::get('login', [TubAuthController::class, 'login'])->name('login.tub');
Route::get('/api/business_hours', [ResourceController::class, 'getBusinessHours'])->name('business_hours.get');
Route::get('/api/resources', [ResourceController::class, 'getResources'])->name('resources.get');
Route::post('/api/reservation/add', [ReservationController::class, 'addReservation'])->name('reservation.add');
