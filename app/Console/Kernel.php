<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\GenerarDespachoCiclicoDiario; // <-- IMPORTANTE: Agrega esta línea para que reconozca el Job

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 1. Tu Job de despacho diario
        $schedule->job(new GenerarDespachoCiclicoDiario)->daily();

        // 2. Worker bajo demanda para el reporte masivo
        $schedule->command('queue:work --stop-when-empty --timeout=0 --memory=1024')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}