<?php

use App\Http\Controllers\Admin\AdminCommunityContributionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlindBoxController;
use App\Http\Controllers\CommunityContributionController;
use App\Http\Controllers\HeritageShopController;
use App\Http\Controllers\PassportController;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->middleware('guest')->name('login');
Route::get('/auth/google', [AuthController::class, 'redirect'])->middleware('guest')->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback'])->middleware('guest');
Route::get('/admin-login', [AuthController::class, 'showAdminLogin'])
    ->name('admin.login');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])
    ->middleware('throttle:6,1')
    ->name('admin.login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', function () {
    if (auth()->check()) {
        return view('user-home', ['userName' => auth()->user()->name]);
    }

    return view('landing');
})->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

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
    });

// Blind Box routes
Route::get('/blind-box', [BlindBoxController::class, 'index'])->name('blind-box.index');
Route::post('/blind-box/draw', [BlindBoxController::class, 'draw'])->name('blind-box.draw');
Route::get('/blind-box/history', [BlindBoxController::class, 'history'])->name('blind-box.history');

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

// Food trails page
Route::get('/foodtrails', function () {
    return view('foodtrails');
});

// Start trail page
Route::get('/start_trail', function () {
    return view('start_trail');
});

Route::get('/heritage-shops', [HeritageShopController::class, 'index'])->name('heritage-shops.index');

// Heritage shop detail
Route::get('/heritage-shops/{id}', [HeritageShopController::class, 'show'])->name('heritage-shops.show');
