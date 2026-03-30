<?php

namespace App\Providers;

use App\Services\TaxService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        // Register TaxService as a singleton so rates are loaded from the
        // settings table only once per request lifecycle.
        $this->app->singleton(TaxService::class, function () {
            return new TaxService();
        });
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        //
    }
}
