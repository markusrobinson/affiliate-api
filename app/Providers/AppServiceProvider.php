<?php

namespace App\Providers;

use App\Enums\AffiliateProvider;
use App\Providers\Affiliate\AmazonProvider;
use App\Providers\Affiliate\WalmartProvider;
use App\Services\AffiliateProviderRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AffiliateProviderRegistry::class, function (): AffiliateProviderRegistry {
            $registry = new AffiliateProviderRegistry;
            $registry->register(AffiliateProvider::Walmart, new WalmartProvider);
            $registry->register(AffiliateProvider::Amazon, new AmazonProvider);

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
