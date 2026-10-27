<?php

namespace App\Providers;

use App\Http\Kernel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Kernel $kernel): void
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);

        // Buyer WhatsApp alerts on every shipment status change (AiSensy)
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
    }
}
