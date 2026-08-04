<?php

use App\Http\Controllers\CommunityContributionController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CommunityContributionController::class, 'index'])->name('home');
Route::post('/community-contributions/heritage-shop', [CommunityContributionController::class, 'store'])->name('community-contribution.store');

Route::redirect('/login', '/')->name('login');

Route::get('/auth/google', [AuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback']);