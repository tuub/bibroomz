<?php

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Models\Resource;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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
    return Inertia::render('App');
})->name('home');

Route::post('/check', [LoginController::class, 'check'])->name('check');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');


Route::middleware('auth:sanctum')->group(function() {
    Route::get('/my', function () {
        return Inertia::render('Profile', [
            'time' => now()->toTimeString(),
            'user' => auth()->user(),
            'user_reservations' => auth()->user()->reservations,
        ]);
    });
    Route::get('/my/events', [UserController::class, 'getEvents'])->name('user_events');
});

Route::middleware('auth')->group(function() {

    Route::post('/reservations/add', [ReservationController::class, 'addReservation'])->name('reservation.add');

    Route::get('/admin/resources', function () {
        return Inertia::render('Admin/Resources/Index', [
            'resources' => Resource::query()
                ->when(Request::input('search'), function ($query, $search) {
                    $query->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('location', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                })
                ->paginate(10)
                ->withQueryString()
                ->through(fn($resource) => [
                    'id' => $resource->id,
                    'title' => $resource->title,
                    'location' => $resource->location,
                ]),
            'filters' => Request::only(['search'])
        ]);
    });

    Route::get('/admin/resources/create', function() {
        return Inertia::render('Admin/Resources/Create');
    });

    Route::post('/admin/resources', function() {
        sleep(3);
        // Validate
        $attributes = Request::validate([
            'institution_id' => ['required', 'numeric'],
            'title' => 'required',
            'location' => 'required',
            'description' => 'required',
            'capacity' => ['numeric', 'gt:0']
        ]);
        // Create
        Resource::create($attributes);
        // Redirect
        return redirect('/admin/resources');
    });
});


Route::get('/api/business_hours', [ResourceController::class, 'getBusinessHours'])->name('business_hours.get');
Route::get('/api/resources', [ResourceController::class, 'getResources'])->name('resources.get');
Route::get('/api/reservations', [ReservationController::class, 'getReservations'])->name('reservations.get');

