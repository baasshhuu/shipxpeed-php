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
        // API v1: B2B status changes go to the client's webhook URL too
        \App\Models\B2bOrder::updated(fn (\App\Models\B2bOrder $o) => \App\Support\ApiV1\WebhookPusher::b2bChanged($o));
    }
}
