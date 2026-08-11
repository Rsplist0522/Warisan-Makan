<?php

use App\Http\Controllers\Admin\AdminCommunityContributionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityContributionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::get('/auth/google', [AuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'callback'])->name('auth.google.callback');
});

Route::get('/admin-login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])
    ->middleware('throttle:6,1')
    ->name('admin.login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'regular_user'])->group(function () {
    Route::view('/', 'user-home')->name('home');
    Route::get('/community-contribution', [CommunityContributionController::class, 'index'])
        ->name('community-contribution.create');
    Route::post('/community-contributions/heritage-shop', [CommunityContributionController::class, 'store'])
        ->name('community-contribution.store');

    Route::get('/community-contributions/drafts', [CommunityContributionController::class, 'drafts'])
        ->name('community-contribution.drafts');
    Route::post('/community-contributions/drafts/{contribution}/submit', [CommunityContributionController::class, 'submitDraft'])
        ->name('community-contribution.drafts.submit');
    Route::delete('/community-contributions/drafts/{contribution}', [CommunityContributionController::class, 'destroyDraft'])
        ->name('community-contribution.drafts.destroy');

    Route::get('/community-contributions', [CommunityContributionController::class, 'contributions'])
        ->name('community-contribution.contributions');
    Route::get('/community-contributions/{contribution}/edit', [CommunityContributionController::class, 'edit'])
        ->name('community-contribution.edit');
    Route::put('/community-contributions/{contribution}', [CommunityContributionController::class, 'update'])
        ->name('community-contribution.update');
    Route::get('/community-contributions/{contribution}', [CommunityContributionController::class, 'show'])
        ->name('community-contribution.contributions.show');
    Route::post('/community-contributions/{contribution}/withdraw', [CommunityContributionController::class, 'withdraw'])
        ->name('community-contribution.contributions.withdraw');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware('admin')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');

        Route::get('/modules/{moduleSlug}', function (string $moduleSlug) {
            $modules = [
                'heritage-registry' => [
                    'name' => 'Heritage Registry',
                    'description' => 'A future workspace for approved shop records, ownership notes, and heritage metadata.',
                ],
                'food-map' => [
                    'name' => 'Food Map',
                    'description' => 'A planned map and discovery module for browsing heritage eateries by location.',
                ],
                'stories-editorial' => [
                    'name' => 'Stories & Editorial',
                    'description' => 'A future editorial queue for oral histories, guides, and feature stories.',
                ],
                'events-trails' => [
                    'name' => 'Events & Trails',
                    'description' => 'A planned module for curated food trails, walking routes, and community events.',
                ],
                'users-roles' => [
                    'name' => 'Users & Roles',
                    'description' => 'A future workspace for contributor profiles, reviewer roles, and access controls.',
                ],
                'reports-analytics' => [
                    'name' => 'Reports & Analytics',
                    'description' => 'A planned reporting area for contribution trends and moderation throughput.',
                ],
            ];

            abort_unless(array_key_exists($moduleSlug, $modules), 404);

            return view('admin.module-placeholder', [
                'module' => $modules[$moduleSlug],
            ]);
        })->name('modules.show');

        Route::prefix('community-contributions')
            ->name('community-contributions.')
            ->group(function () {
                Route::get('/submissions', [AdminCommunityContributionController::class, 'submissions'])->name('submissions');
                Route::get('/history', [AdminCommunityContributionController::class, 'history'])->name('history');
                Route::get('/{contribution}', [AdminCommunityContributionController::class, 'show'])->name('show');
                Route::post('/{contribution}/start-review', [AdminCommunityContributionController::class, 'startReview'])->name('start-review');
                Route::post('/{contribution}/moderate', [AdminCommunityContributionController::class, 'moderate'])->name('moderate');
            });
    });
