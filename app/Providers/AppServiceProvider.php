<?php

namespace App\Providers;

use App\Services\ShopApiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ShopApiService::class, function ($app) {
            return new ShopApiService();
        });
    }

    public function boot(): void
    {
        //
    }
}
