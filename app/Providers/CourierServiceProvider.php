<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CourierServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Automatically bind each code => class from config/couriers.php
        foreach (config('couriers') as $code => $class) {
            $this->app->bind("courier.{$code}", fn() => new $class);
        }

        // Shipclues aggregator (bound here so it works even when config is cached)
        $this->app->bind('courier.shipclues', fn() => new \App\Services\ShipcluesService);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->linkShipcluesProvider();
    }

    /**
     * Make sure a logistic_providers row with code "shipclues" exists so Shipclues
     * couriers are offered to every seller:
     *  - a provider added in Admin > Logistics with the name "Shipclues" gets code "shipclues";
     *  - otherwise, when SHIPCLUES_API_KEY is set in .env, the row is created automatically.
     */
    private function linkShipcluesProvider(): void
    {
        try {
            Cache::remember('shipclues_provider_linked', now()->addMinutes(10), function () {
                $model = \App\Models\LogisticProvider::class;

                if ($model::where('code', 'shipclues')->exists()) {
                    return true;
                }

                $byName = $model::whereRaw('LOWER(TRIM(name)) = ?', ['shipclues'])
                    ->where(fn ($q) => $q->whereNull('code')->orWhere('code', ''))
                    ->first();
                if ($byName) {
                    $byName->code = 'shipclues';
                    $byName->save();
                    return true;
                }

                if (env('SHIPCLUES_API_KEY')) {
                    $row = new $model();
                    $row->name = 'Shipclues';
                    $row->code = 'shipclues';
                    $row->email = 'shipclues@shipxpeed.com';
                    $row->password = bcrypt(\Illuminate\Support\Str::random(32));
                    $row->logo = 'brands/image.png';
                    $row->status = 1;
                    $row->save();
                    return true;
                }

                return false;
            });
        } catch (\Throwable $e) {
            Log::warning('Shipclues provider link skipped: ' . $e->getMessage());
        }
    }
}
