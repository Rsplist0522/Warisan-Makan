<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HeritageShopController;


// Login page
Route::get('/login', function () {
    return view('auth.login');
});


// Google Login
Route::get('/auth/google', [AuthController::class, 'redirect']);

Route::get('/auth/google/callback', [AuthController::class, 'callback']);


// Temporary development shortcut: skip login while the heritage module is being tested.
// Remove this block and the heritage route once authentication is ready.
Route::get('/heritage-shops', [HeritageShopController::class, 'index'])->name('heritage-shops.index');

// Heritage shop detail
Route::get('/heritage-shops/{id}', [HeritageShopController::class, 'show'])->name('heritage-shops.show');

Route::get('/', function () {
    return redirect('/heritage-shops');
});