<?php

namespace Whilesmart\Invoices;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InvoicesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/invoices.php', 'invoices');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/invoices.php' => config_path('invoices.php'),
        ], 'invoices-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'invoices-migrations');

        if (config('invoices.register_routes', true)) {
            Route::middleware(config('invoices.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('invoices.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
