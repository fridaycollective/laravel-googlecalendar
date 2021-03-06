<?php

use FridayCollective\LaravelGoogleCalendar\Http\Controllers\OAuthController;
use FridayCollective\LaravelGoogleCalendar\Http\Controllers\UserGoogleCalendarController;
use FridayCollective\LaravelGoogleCalendar\Http\Controllers\PubSubController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/oauth/google-calendar/callback', [OAuthController::class, 'googleCalendarCallback']);
});

Route::middleware(Config::get('googlecalendar.middleware'))->group(function () {
    Route::prefix('api')->group(function () {
        Route::get('/calendar-integration-config', [OAuthController::class, 'fetchCalendarIntegrationConfig']);
        Route::put('/calendar-integration-config', [OAuthController::class, 'updateCalendarIntegrationConfig']);

        Route::post('/user-google-calendars/refresh', [UserGoogleCalendarController::class, 'refresh']);
        Route::apiResource('user-google-calendars', UserGoogleCalendarController::class);

        Route::prefix('oauth')->group(function () {
            Route::prefix('google-calendar')->group(function () {
                Route::get('/', [OAuthController::class, 'googleCalendarRedirect']);
                Route::post('/logout', [OAuthController::class, 'googleCalendarLogout']);
            });
        });
    });
});