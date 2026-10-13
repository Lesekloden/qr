<?php

declare(strict_types=1);

namespace Lesekloden\Qr;

use Illuminate\Support\ServiceProvider;
use Lesekloden\Qr\Console\Commands\GenerateQrCodesCommand;

class QrServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/qr.php', 'qr');

        $this->app->singleton(QrCodeGenerator::class);
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

        $this->publishes([
            __DIR__.'/../config/qr.php' => config_path('qr.php'),
        ], ['lesekloden-qr', 'lesekloden-qr-config']);

        $this->commands([GenerateQrCodesCommand::class]);
    }
}
