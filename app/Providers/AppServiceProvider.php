<?php

namespace App\Providers;

use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\User;
use App\Policies\HeritageShopContributionPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->guardTestingDatabase();

        Gate::policy(HeritageShopContribution::class, HeritageShopContributionPolicy::class);

        Relation::enforceMorphMap([
            'correction_request' => CorrectionRequest::class,
            'heritage_shop' => HeritageShop::class,
            'heritage_shop_contribution' => HeritageShopContribution::class,
            'user' => User::class,
        ]);
    }

    private function guardTestingDatabase(): void
    {
        if (! $this->app->environment('testing')) {
            return;
        }

        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");
        $host = (string) config("database.connections.{$connection}.host", '');

        $isSafeSqliteTestDatabase = $connection === 'sqlite' && $database === ':memory:';
        $isSafeMysqlTestDatabase = $connection === 'mysql'
            && $database === 'warisan_makan_testing'
            && ! str_contains(strtolower($host), 'aivencloud.com');

        if (! $isSafeSqliteTestDatabase && ! $isSafeMysqlTestDatabase) {
            throw new RuntimeException(
                'Refusing to run tests unless using SQLite :memory: or MySQL database warisan_makan_testing.'
            );
        }
    }
}
