<?php

namespace App\Providers;

use App\Services\MultiHostService;
use App\Services\Hosting\DoodstreamDriver;
use App\Services\Hosting\StreamtapeDriver;
use App\Services\Hosting\SeekstreamingDriver;
use App\Services\Hosting\VideySimulator;
use Illuminate\Support\ServiceProvider;

class MultiHostServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MultiHostService::class, function ($app) {
            $service = new MultiHostService();

            // === FOCUSED MODE: Streamtape only ===
            // Other providers disabled to prioritize distribution efficiency.
            $service->registerDriver(new \App\Services\Hosting\StreamtapeDriver('ba1402c48869ff3f5d71', 'ZKbxp4w8VjIqxXb'));

            return $service;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
