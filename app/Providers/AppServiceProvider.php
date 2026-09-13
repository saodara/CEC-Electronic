<?php

namespace App\Providers;

use App\Database\RetryingPostgresConnection;
use App\Database\RetryingPostgresConnector;
use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Swap in a pgsql connector/connection that retries with a short
        // backoff on transient DNS/network blips talking to Neon, instead of
        // Laravel's default single, immediate retry.
        $this->app->bind('db.connector.pgsql', RetryingPostgresConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Connection::resolverFor('pgsql', fn ($connection, $database, $prefix, $config) => new RetryingPostgresConnection($connection, $database, $prefix, $config));

        // The default Tailwind pagination view renders unstyled here since the
        // app doesn't load Tailwind; this one reuses the shared .btn/.pager
        // classes already defined in the admin and shop layouts.
        Paginator::defaultView('vendor.pagination.custom');
    }
}
