<?php

namespace Gnireeg\LaravelTimezoned;

use Illuminate\Support\ServiceProvider;

class LaravelTimezonedServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/timezoned.php',
            'timezoned'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/timezoned.php' => config_path('timezoned.php'),
        ], 'config');
    }
}