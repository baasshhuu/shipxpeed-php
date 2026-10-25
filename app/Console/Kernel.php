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

        // Shipclues: refresh tracking status of open shipments
        $schedule
            ->command('shipclues:track')
            ->everyThirtyMinutes()
            ->withoutOverlapping(30)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/shipclues-track.log'));

        // Fship: pull tracking status for open shipments
        $schedule
        ->command('fship:track')
        ->everyThirtyMinutes()
        ->withoutOverlapping(25)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/fship-track.log'));

        // Shopify: pull new orders + push AWB / shipment status back
        $schedule
        ->command('shopify:sync')
        ->everyFiveMinutes()
        ->withoutOverlapping(20)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/shopify-sync.log'));
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
