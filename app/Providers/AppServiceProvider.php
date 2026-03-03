<?php

namespace App\Providers;

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
        if (app()->runningInConsole()) {
            return;
        }

        if (file_exists(public_path('hot'))) {
            return;
        }

        $request = request();
        if (! $request) {
            return;
        }

        $assetBaseUrl = rtrim($request->getSchemeAndHttpHost() . $request->getBasePath(), '/');
        if ($assetBaseUrl !== '') {
            config(['app.asset_url' => $assetBaseUrl]);
        }
    }
}
