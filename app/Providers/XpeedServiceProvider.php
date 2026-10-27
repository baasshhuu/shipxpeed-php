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
        }

        if ($this->app->runningInConsole()) {
            $this->commands([\App\Console\Commands\XpeedServices::class]);
            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('xpeed:services')
                    ->hourlyAt(7)
                    ->withoutOverlapping(50)
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/xpeed-services.log'));
            });
        }
    }
}
