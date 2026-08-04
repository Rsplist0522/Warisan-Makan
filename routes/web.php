<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PassportController;


// Login page
Route::get('/login', function () {
    return view('auth.login');
});


// Google Login
Route::get('/auth/google', [AuthController::class, 'redirect']);

Route::get('/auth/google/callback', [AuthController::class, 'callback']);


// Default: redirect to a module-only shop page for development
Route::get('/', function () {
    return redirect('/foodPassport/shop/1');
});

// Module-only shop check-in page (public for development)
Route::get('/foodPassport/shop/{id}', [PassportController::class, 'showShop'])
    ->name('passport.shop');

// Food Passport 
// Public Food Passport page (no login required for viewing)
Route::get('/foodPassport', [PassportController::class, 'index'])
    ->name('passport.index');

// Protected endpoints for authenticated users
Route::middleware('auth')->group(function () {

    Route::post('/passport/check-in', [PassportController::class, 'checkIn'])
        ->name('passport.checkin');

});

// Public test endpoint for local development: simulate a logged-in user
Route::post('/passport/check-in-test', [PassportController::class, 'checkInTest'])
    ->name('passport.checkin.test');