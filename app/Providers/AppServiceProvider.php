<?php

namespace App\Providers;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'correction_request' => CorrectionRequest::class,
            'heritage_shop' => HeritageShop::class,
            'heritage_shop_contribution' => HeritageShopContribution::class,
            'user' => User::class,
        ]);
    }
}
