<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Debian's nginx fastcgi_params passes HTTP_HOST as $host (no port) rather
        // than $http_host, so request-derived URLs drop the port behind Docker's
        // port mapping. Force the root URL from APP_URL instead of trusting the
        // request host.
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
