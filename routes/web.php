<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


// Login page
Route::get('/login', function () {
    return view('auth.login');
});


// Google Login
Route::get('/auth/google', [AuthController::class, 'redirect']);

Route::get('/auth/google/callback', [AuthController::class, 'callback']);


// Optional: make homepage go to login
Route::get('/', function () {
    return redirect('/login');
});