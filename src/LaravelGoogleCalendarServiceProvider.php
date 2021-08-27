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

        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
