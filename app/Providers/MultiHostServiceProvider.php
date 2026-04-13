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
            $service = new \App\Services\MultiHostService();

            // Fetch credentials from Settings table
            $stLogin = \App\Models\Setting::getValue('streamtape_login', 'ba1402c48869ff3f5d71');
            $stKey   = \App\Models\Setting::getValue('streamtape_key', 'ZKbxp4w8VjIqxXb');
            $dsKey   = \App\Models\Setting::getValue('doodstream_key', '561492l0isghhqftfzqpnv');

            // Register Streamtape (Server 1)
            $service->registerDriver(new \App\Services\Hosting\StreamtapeDriver($stLogin, $stKey));

            // Register Doodstream (Server 2) - Priority v3.0
            if (!empty($dsKey)) {
                 $service->registerDriver(new \App\Services\Hosting\DoodstreamDriver($dsKey));
            }

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
