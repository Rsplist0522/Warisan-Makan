<?php

use App\Http\Controllers\AdminCommunityContributionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityContributionController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->middleware('guest')->name('login');
Route::get('/auth/google', [AuthController::class, 'redirect'])->middleware('guest')->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/', [CommunityContributionController::class, 'index'])->name('home');
    Route::get('/community-contributions/create', [CommunityContributionController::class, 'create'])
        ->name('community-contribution.create');
    Route::post('/community-contributions/heritage-shop', [CommunityContributionController::class, 'store'])
        ->name('community-contribution.store');

    Route::get('/community-contributions/drafts', [CommunityContributionController::class, 'drafts'])
        ->name('community-contribution.drafts');
    Route::get('/community-contributions/{contribution}/edit', [CommunityContributionController::class, 'edit'])
        ->name('community-contribution.edit');
    Route::put('/community-contributions/{contribution}', [CommunityContributionController::class, 'update'])
        ->name('community-contribution.update');
    Route::delete('/community-contributions/{contribution}/draft', [CommunityContributionController::class, 'destroyDraft'])
        ->name('community-contribution.drafts.destroy');
    Route::post('/community-contributions/{contribution}/submit', [CommunityContributionController::class, 'submitDraft'])
        ->name('community-contribution.drafts.submit');

    Route::get('/community-contributions', [CommunityContributionController::class, 'contributions'])
        ->name('community-contribution.contributions');
    Route::get('/community-contributions/{contribution}', [CommunityContributionController::class, 'show'])
        ->name('community-contribution.contributions.show');
    Route::post('/community-contributions/{contribution}/withdraw', [CommunityContributionController::class, 'withdraw'])
        ->name('community-contribution.contributions.withdraw');

    Route::prefix('admin/community-contributions')->name('admin.community-contributions.')
        ->middleware('admin')->group(function (): void {
            Route::get('/submissions', [AdminCommunityContributionController::class, 'submissions'])->name('submissions');
            Route::get('/history', [AdminCommunityContributionController::class, 'history'])->name('history');
            Route::get('/{contribution}', [AdminCommunityContributionController::class, 'show'])->name('show');
            Route::post('/{contribution}/start-review', [AdminCommunityContributionController::class, 'startReview'])->name('start-review');
            Route::post('/{contribution}/moderate', [AdminCommunityContributionController::class, 'moderate'])->name('moderate');
        });
});
