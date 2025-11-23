<?php

namespace Gnireeg\LaravelTimezoned\Tests;

use Gnireeg\LaravelTimezoned\LaravelTimezonedServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelTimezonedServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.timezone', 'UTC');
        $app['config']->set('timezoned.database_timezone', 'UTC');
        $app['config']->set('timezoned.timezone_resolver', fn () => 'UTC');
    }
}
