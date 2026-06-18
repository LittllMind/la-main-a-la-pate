<?php

namespace App\Providers;

use App\Helpers\RoleHelper;
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
        \Illuminate\Support\Facades\Blade::directive('admin', function () {
            return "<?php if (\\App\\Helpers\\RoleHelper::isAdmin()): ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('endadmin', function () {
            return '<?php endif; ?>';
        });
    }
}
