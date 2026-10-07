<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}