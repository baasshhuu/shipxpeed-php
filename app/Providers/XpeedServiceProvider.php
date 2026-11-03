<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Seller Control Suite: registers its routes (routes/xpeed.php) and its hourly job,
 * so the existing route files and console kernel don't have to be edited.
 */
class XpeedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!$this->app->routesAreCached()) {
            Route::middleware('web')->group(base_path('routes/xpeed.php'));
            if (file_exists(base_path('routes/cod.php'))) {
                // loaded after web/admin routes, so it takes over /seller/cod-remittance and /codremittance
                Route::middleware('web')->group(base_path('routes/cod.php'));
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands(array_filter([\App\Console\Commands\XpeedServices::class, class_exists(\App\Console\Commands\CodRun::class) ? \App\Console\Commands\CodRun::class : null,class_exists(\App\Console\Commands\WeightAutoFetchCommand::class) ? \App\Console\Commands\WeightAutoFetchCommand::class : null]));
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('xpeed:services')
                    ->hourlyAt(7)
                    ->withoutOverlapping(50)
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/xpeed-services.log'));
                if (class_exists(\App\Console\Commands\CodRun::class)) {
                    // COD: generate for new deliveries + wallet top-ups, every 30 minutes
                    $schedule->command('cod:run')
                        ->everyThirtyMinutes()
                        ->withoutOverlapping(25)
                        ->runInBackground()
                        ->appendOutputTo(storage_path('logs/cod-run.log'));
                }
                if (class_exists(\App\Console\Commands\WeightAutoFetchCommand::class)) {
                    $schedule->command('weight:auto-fetch')
                        ->hourlyAt(23)
                        ->withoutOverlapping(55)
                        ->runInBackground()
                        ->appendOutputTo(storage_path('logs/weight-auto-fetch.log'));
                }
            });
        }
    }
}
