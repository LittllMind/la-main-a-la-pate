<?php

namespace App\Providers;

use App\Services\Analytics\AnalyticsRecorder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AnalyticsRecorder::class, function () {
            return new AnalyticsRecorder(Request::instance() ?: request());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
