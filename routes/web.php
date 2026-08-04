<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlindBoxController;

// Login page
Route::get('/login', function () {
    return view('auth.login');
});

// Google Login
Route::get('/auth/google', [AuthController::class, 'redirect']);
Route::get('/auth/google/callback', [AuthController::class, 'callback']);

// Blind Box routes
Route::get('/blind-box', [BlindBoxController::class, 'index'])->name('blind-box.index');
Route::post('/blind-box/draw', [BlindBoxController::class, 'draw'])->name('blind-box.draw');
Route::get('/blind-box/history', [BlindBoxController::class, 'history'])->name('blind-box.history');

// Homepage goes straight to the landing page experience
Route::get('/', [BlindBoxController::class, 'index']);