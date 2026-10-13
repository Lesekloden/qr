<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

use Illuminate\Support\ServiceProvider;

class QrServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Qr::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
    }
}
