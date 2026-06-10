<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Whilesmart\Customers\CustomersServiceProvider;
use Whilesmart\Invoices\InvoicesServiceProvider;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;
use Whilesmart\Payments\PaymentsServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/whilesmart/eloquent-customers/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../vendor/whilesmart/eloquent-payments/database/migrations');

        Schema::create('workspaces', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            OwnerAccessServiceProvider::class,
            CustomersServiceProvider::class,
            PaymentsServiceProvider::class,
            InvoicesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('invoices.route_middleware', ['api']);
        $app['config']->set('customers.route_middleware', ['api']);
    }
}
