<?php

namespace FridayCollective\LaravelGoogleCalendar\Jobs;

use Carbon\Carbon;
use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;
use FridayCollective\LaravelGoogleCalendar\Services\Google\Calendar\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshGoogleCalendarNotificationChannels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $calendarConfigs = UserCalendarIntegrationConfig::where('type', 'google')
            ->where('status', 'active')
            ->get();

        foreach ($calendarConfigs as $calendarConfig) {
            try {
                foreach ($calendarConfig->googleCalendars as $googleCalendar) {
                    $expiresMs = intval($googleCalendar->google_notification_channel_expiration);
                    if ($expiresMs < Carbon::now()->timestamp * 1000) {
                        $calendarService = new GoogleCalendarService($calendarConfig);
                        try {
                            $calendarService->unsubscribeFromCalendarNotifications($googleCalendar);
                        } catch (\Exception $e) {

                        }
                        $calendarService->subscribeToCalendarNotifications($googleCalendar);
                    }
                }

            } catch (\Exception $e) {
                Log::error($e);
                Log::error('Error refreshing calendar integration for user ' . $calendarService->user->id);
            }
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        Log::error($exception);
    }
}
