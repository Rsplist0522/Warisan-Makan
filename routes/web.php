<?php

use App\Http\Controllers\Admin\AdminCommunityContributionController;
use App\Http\Controllers\Admin\BadgeManagementController;
use App\Http\Controllers\Admin\BlindBoxController as AdminBlindBoxController;
use App\Http\Controllers\Admin\HeritageFoodItemAdminController;
use App\Http\Controllers\Admin\HeritageShopAdminController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlindBoxController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommunityContributionController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\FoodTrailController;
use App\Http\Controllers\Admin\FoodTrailSuggestionController;
use App\Http\Controllers\HeritageShopController;
use App\Http\Controllers\PassportController;
use App\Models\SiteBranding;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->middleware('guest')->name('login');
Route::get('/auth/google', [AuthController::class, 'redirect'])->middleware('guest')->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback'])->middleware('guest');
Route::get('/guest', function (\Illuminate\Http\Request $request) {
    $request->session()->regenerate();
    $request->session()->put('guest_mode', true);

    return redirect()->route('user.dashboard');
})->middleware('guest')->name('guest.continue');
Route::get('/admin-login', [AuthController::class, 'showAdminLogin'])
    ->name('admin.login');
Route::post('/admin-login', [AuthController::class, 'adminLogin'])
    ->middleware('throttle:6,1')
    ->name('admin.login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/brand-logo', function () {
    $branding = SiteBranding::current();

    abort_unless($branding?->hasLogo(), 404);

    $logoBytes = $branding->logoBytes();

    return response($logoBytes, 200, [
        'Content-Type' => $branding->logo_mime_type,
        'Content-Length' => (string) strlen($logoBytes),
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('brand.logo');

Route::view('/landing', 'landing')->name('landing');

$userDashboard = function (\Illuminate\Http\Request $request) {
    if (! $request->user() && ! (bool) $request->session()->get('guest_mode')) {
        return redirect()->route('login');
    }

    if ($request->user()?->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    if ($request->user()?->isBlocked()) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'login' => __('Your account has been blocked. Please contact the administrator to regain access.'),
        ]);
    }

    return view('user-home', [
        'userName' => $request->user()?->name ?? 'Food Explorer',
    ]);
};

Route::get('/', $userDashboard)->middleware(['system.access', 'user.inactivity'])->name('home');
Route::get('/dashboard', $userDashboard)->middleware(['system.access', 'user.inactivity'])->name('user.dashboard');

Route::middleware(['auth', 'active_user', 'user.inactivity'])->group(function (): void {
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
    Route::post('/community-contributions/{contribution}/edit-resubmit', [CommunityContributionController::class, 'editResubmit'])
        ->name('community-contribution.contributions.edit-resubmit');

    Route::get('/community-contributions', [CommunityContributionController::class, 'contributions'])
        ->name('community-contribution.contributions');
    Route::get('/community-contributions/correction-requests', [CorrectionRequestController::class, 'index'])
        ->name('community-contribution.correction-requests');
    Route::get('/community-contributions/correction-requests/{correctionRequest}', [CorrectionRequestController::class, 'show'])
        ->name('community-contribution.correction-requests.show');
    Route::post('/community-contributions/correction-requests/{correctionRequest}/additional-information', [CorrectionRequestController::class, 'provideInformation'])
        ->name('community-contribution.correction-requests.additional-information');
    Route::get('/heritage-shops/{heritageShop}/correction-requests/create', [CorrectionRequestController::class, 'create'])
        ->name('heritage-shops.correction-requests.create');
    Route::post('/heritage-shops/{heritageShop}/correction-requests', [CorrectionRequestController::class, 'store'])
        ->name('heritage-shops.correction-requests.store');
    Route::get('/community-contributions/{contribution}', [CommunityContributionController::class, 'show'])
        ->withTrashed()
        ->name('community-contribution.contributions.show');
    Route::post('/community-contributions/{contribution}/withdraw', [CommunityContributionController::class, 'withdraw'])
        ->name('community-contribution.contributions.withdraw');

    Route::prefix('admin/community-contributions')->name('admin.community-contributions.')
        ->middleware('admin')->group(function (): void {
            Route::get('/submissions', [AdminCommunityContributionController::class, 'submissions'])->name('submissions');
            Route::get('/history', [AdminCommunityContributionController::class, 'history'])->name('history');
            Route::get('/correction-requests', [AdminCommunityContributionController::class, 'correctionRequests'])->name('correction-requests');
            Route::get('/correction-requests/{correctionRequest}', [AdminCommunityContributionController::class, 'showCorrectionRequest'])->name('correction-requests.show');
            Route::post('/correction-requests/{correctionRequest}/start-review', [AdminCommunityContributionController::class, 'startCorrectionReview'])->name('correction-requests.start-review');
            Route::post('/correction-requests/{correctionRequest}/moderate', [AdminCommunityContributionController::class, 'moderateCorrectionRequest'])->name('correction-requests.moderate');
            Route::get('/{contribution}', [AdminCommunityContributionController::class, 'show'])->withTrashed()->name('show');
            Route::post('/{contribution}/start-review', [AdminCommunityContributionController::class, 'startReview'])->name('start-review');
            Route::post('/{contribution}/moderate', [AdminCommunityContributionController::class, 'moderate'])->name('moderate');
    });
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['admin', 'user.inactivity'])
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');

        Route::get('/users', [UserManagementController::class, 'index'])
            ->name('users.index');

        Route::get('/users/{user}', [UserManagementController::class, 'show'])
            ->whereNumber('user')
            ->name('users.show');

        Route::resource('food-trails', FoodTrailSuggestionController::class)->except('show');
        Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::prefix('badges')->name('badges.')->group(function (): void {
            Route::get('/', [BadgeManagementController::class, 'index'])->name('index');
            Route::get('/create', [BadgeManagementController::class, 'create'])->name('create');
            Route::post('/', [BadgeManagementController::class, 'store'])->name('store');
            Route::get('/{badge}/edit', [BadgeManagementController::class, 'edit'])->name('edit');
            Route::put('/{badge}', [BadgeManagementController::class, 'update'])->name('update');
            Route::patch('/{badge}/toggle', [BadgeManagementController::class, 'toggle'])->name('toggle');
        });

        Route::prefix('heritage-shops')->name('heritage-shops.')->group(function () {
            Route::get('/', [HeritageShopAdminController::class, 'index'])->name('index');
            Route::get('/create', [HeritageShopAdminController::class, 'create'])->name('create');
            Route::post('/', [HeritageShopAdminController::class, 'store'])->name('store');
            Route::get('/{heritageShop}/edit', [HeritageShopAdminController::class, 'edit'])->name('edit');
            Route::put('/{heritageShop}', [HeritageShopAdminController::class, 'update'])->name('update');
            Route::delete('/{heritageShop}', [HeritageShopAdminController::class, 'destroy'])->name('destroy');
            Route::post('/crawl', [HeritageShopAdminController::class, 'crawl'])->middleware('throttle:10,1')->name('crawl');
            Route::post('/discover', [HeritageShopAdminController::class, 'discover'])->middleware('throttle:10,1')->name('discover');
            Route::post('/discover/import', [HeritageShopAdminController::class, 'importDiscovered'])->name('discover.import');
            Route::get('/{heritageShop}/food-items', [HeritageFoodItemAdminController::class, 'index'])->name('food-items.index');
            Route::get('/{heritageShop}/food-items/{foodItem}/image', [HeritageFoodItemAdminController::class, 'image'])->name('food-items.image');
            Route::post('/{heritageShop}/food-items', [HeritageFoodItemAdminController::class, 'store'])->name('food-items.store');
            Route::put('/{heritageShop}/food-items/{foodItem}', [HeritageFoodItemAdminController::class, 'update'])->name('food-items.update');
            Route::patch('/{heritageShop}/food-items/{foodItem}/toggle', [HeritageFoodItemAdminController::class, 'toggle'])->name('food-items.toggle');
            Route::delete('/{heritageShop}/food-items/{foodItem}', [HeritageFoodItemAdminController::class, 'destroy'])->name('food-items.destroy');
        });

        Route::prefix('blind-box-items')->name('blind-box-items.')->group(function (): void {
            Route::get('/', [AdminBlindBoxController::class, 'index'])->name('index');
            Route::get('/{shop}/edit', [AdminBlindBoxController::class, 'edit'])->whereNumber('shop')->name('edit');
            Route::post('/available/{sourceShop}', [AdminBlindBoxController::class, 'add'])->whereNumber('sourceShop')->name('add');
            Route::put('/{shop}', [AdminBlindBoxController::class, 'update'])->whereNumber('shop')->name('update');
            Route::patch('/{shop}/toggle', [AdminBlindBoxController::class, 'toggle'])->whereNumber('shop')->name('toggle');
        });

    });

// Blind Box routes
Route::get('/blind-box', [BlindBoxController::class, 'index'])->middleware(['auth', 'user.inactivity'])->name('blind-box.index');
Route::post('/blind-box/draw', [BlindBoxController::class, 'draw'])->middleware(['auth', 'user.inactivity'])->name('blind-box.draw');
Route::post('/chat', [ChatController::class, 'respond'])->name('chat.respond');

// Module-only shop check-in page (public for development)
Route::get('/foodPassport/shop/{id}', [PassportController::class, 'showShop'])->middleware(['auth', 'user.inactivity'])
    ->name('passport.shop');

// Food Passport 
// Public Food Passport page (no login required for viewing)
Route::get('/foodPassport', [PassportController::class, 'index'])->middleware(['auth', 'user.inactivity'])
    ->name('passport.index');
Route::get('/foodPassport/history', [PassportController::class, 'history'])->middleware(['auth', 'user.inactivity'])
    ->name('passport.history');
Route::get('/foodPassport/statistics', [PassportController::class, 'statistics'])->middleware(['auth', 'user.inactivity'])
    ->name('passport.statistics');
Route::get('/foodPassport/leaderboard', [PassportController::class, 'leaderboard'])->middleware(['auth', 'user.inactivity'])
    ->name('passport.leaderboard');

// Protected endpoints for authenticated users
Route::middleware(['auth', 'user.inactivity'])->group(function () {

    Route::post('/passport/check-in', [PassportController::class, 'checkIn'])
        ->name('passport.checkin');
});


// Food trails page
Route::get('/foodtrails', [FoodTrailController::class, 'index'])->middleware(['auth', 'user.inactivity'])->name('foodtrails.index');

// Start trail page
Route::get('/start_trail', function () {
    return view('start_trail');
})->middleware(['auth', 'user.inactivity']);

Route::get('/heritage-shops', [HeritageShopController::class, 'index'])
    ->middleware(['system.access', 'user.inactivity'])
    ->name('heritage-shops.index');
Route::get('/heritage-shops/{heritageShop}/menu', [HeritageShopController::class, 'menu'])
    ->whereNumber('heritageShop')
    ->middleware(['auth', 'user.inactivity'])
    ->name('heritage-shops.menu');
Route::get('/heritage-shops/{heritageShop}/images/{image}', [HeritageShopController::class, 'image'])
    ->whereNumber('heritageShop')
    ->whereNumber('image')
    ->middleware(['auth', 'user.inactivity'])
    ->name('heritage-shops.images.show');
Route::get('/heritage-shops/{heritageShop}/food-items/{foodItem}', [HeritageShopController::class, 'foodItem'])
    ->whereNumber('heritageShop')
    ->whereNumber('foodItem')
    ->middleware(['auth', 'user.inactivity'])
    ->name('heritage-shops.food-items.show');
Route::get('/heritage-shops/{heritageShop}/food-items/{foodItem}/image', [HeritageShopController::class, 'foodImage'])
    ->whereNumber('heritageShop')
    ->whereNumber('foodItem')
    ->middleware(['auth', 'user.inactivity'])
    ->name('heritage-shops.food-images.show');
Route::post('/heritage-shops/{heritageShop}/ai-guide', [HeritageShopController::class, 'aiGuide'])
    ->middleware('throttle:30,1')
    ->middleware(['auth', 'user.inactivity'])
    ->whereNumber('heritageShop')
    ->name('heritage-shops.ai-guide');
Route::get('/heritage-shops/{id}', [HeritageShopController::class, 'show'])
    ->whereNumber('id')
    ->middleware(['auth', 'user.inactivity'])
    ->name('heritage-shops.show');
