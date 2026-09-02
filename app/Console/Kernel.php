<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Purge quotidienne des événements analytics bruts au-delà de la rétention.
        $schedule->command('analytics:purge')->daily();

        // Rapport audience quotidien J-1 à 08:00 Europe/Paris.
        $schedule->command('analytics:daily-report')
            ->dailyAt('08:00')
            ->timezone('Europe/Paris');
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
