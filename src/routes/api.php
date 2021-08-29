<?php
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


use FridayCollective\LaravelGoogleCalendar\Http\Controllers\OAuthController;
use FridayCollective\LaravelGoogleCalendar\Http\Controllers\UserGoogleCalendarController;
use FridayCollective\LaravelGoogleCalendar\Http\Controllers\PubSubController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::prefix('oauth')->group(function () {
        Route::prefix('google-calendar')->group(function () {
            Route::get('/callback', [OAuthController::class, 'googleCalendarCallback']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('api')->group(function () {
        Route::apiResource('user-google-calendars', UserGoogleCalendarController::class);
        Route::get('/calendar-integration-config', [OAuthController::class, 'fetchCalendarIntegrationConfig']);
        Route::prefix('oauth')->group(function () {
            Route::prefix('google-calendar')->group(function () {
                Route::get('/', [OAuthController::class, 'googleCalendarRedirect']);
                Route::post('/logout', [OAuthController::class, 'googleCalendarLogout']);
            });
        });
    });
});