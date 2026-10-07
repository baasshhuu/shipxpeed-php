<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('routeis', function ($expression) {
            return "<?php if (in_array(Route::currentRouteName(), explode(',', $expression))) : ?>";
        });

        Blade::directive('endrouteis', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('routeisnot', function ($expression) {
            return "<?php if (! in_array(Route::currentRouteName(), explode(',',$expression))) : ?>";
        });

        Blade::directive('endrouteisnot', function () {
            return '<?php endif; ?>';
        });

        Blade::directive('currency', function ($money) {
            return "<?php echo number_format(round($money)); ?>";
        });
    }
}
