<?php

namespace App\Providers;


use Illuminate\Support\Facades\URL;

use App\Services\CartService;
use App\Models\Vente;
use App\Observers\VenteObserver;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService();
        });
    }
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vente::observe(VenteObserver::class);

        if (env('APP_ENV') === 'local' && str_contains(config('app.url'), 'ngrok')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
