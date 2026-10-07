<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        // Register your custom commands here if not auto-discovered
        \App\Console\Commands\TrackDelhiveryOrders::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //$schedule->command('db:backup')->twiceDaily(6, 18, 3);
        $schedule->call(function () {
            \Log::channel('scheduler')->info('Cron test: ' . now());
        })->everyMinute();

        $schedule
        ->command('track:delhivery')
        ->everyMinute()
        ->appendOutputTo(storage_path('logs/scheduler.log'));
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
