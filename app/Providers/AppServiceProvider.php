<?php

namespace App\Providers;

use App\Mail\GmailApi\GmailApiClient;
use App\Mail\GmailApi\GmailApiTransport;
use App\Mail\GmailApi\GoogleGmailApiClient;
use App\Models\CorrectionRequest;
use App\Models\HeritageShop;
use App\Models\HeritageShopContribution;
use App\Models\User;
use App\Policies\HeritageShopContributionPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Psr\Log\LoggerInterface;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GmailApiClient::class, function ($app): GmailApiClient {
            return new GoogleGmailApiClient(
                $app->make(HttpFactory::class),
                $app->make(LoggerInterface::class),
                $app['config']->get('services.gmail_api', [])
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->guardTestingDatabase();

        Gate::policy(HeritageShopContribution::class, HeritageShopContributionPolicy::class);

        Relation::enforceMorphMap([
            'correction_request' => CorrectionRequest::class,
            'heritage_shop' => HeritageShop::class,
            'heritage_shop_contribution' => HeritageShopContribution::class,
            'user' => User::class,
        ]);

        Mail::extend('gmail_api', function (array $config = []): GmailApiTransport {
            return new GmailApiTransport(
                $this->app->make(GmailApiClient::class),
                $this->app->make(LoggerInterface::class)
            );
        });
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
