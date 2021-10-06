<?php

namespace FridayCollective\LaravelGoogleCalendar;

use Illuminate\Support\ServiceProvider;

class LaravelGoogleCalendarServiceProvider extends ServiceProvider
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
        $this->publishes([
            __DIR__.'/config/googlecalendar.php' => config_path('googlecalendar.php'),
        ]);

        switch (config('googlecalendar.load_routes_from')) {
            case 'web':
                $this->loadRoutesFrom(__DIR__.'/routes/web.php');
                break;
            case 'api':
                $this->loadRoutesFrom(__DIR__.'/routes/api.php');
                break;
            default:
                break;
        }

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
