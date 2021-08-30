<?php

namespace FridayCollective\LaravelGoogleCalendar\Handlers;


use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;
use FridayCollective\LaravelGoogleCalendar\Models\UserGoogleCalendar;
use FridayCollective\LaravelGoogleCalendar\Services\Google\Calendar\GoogleCalendarService;
use Illuminate\Database\Eloquent\Model;

class CalendarListHandler
{
    public static function syncCalendarList(UserCalendarIntegrationConfig $calendarIntegrationConfig)
    {
        $calendarService = new GoogleCalendarService($calendarIntegrationConfig);
        $calendarList = $calendarService->getCalendarList();

        // create or update items
        foreach ($calendarList->items as $calendar) {
            if ($calendar->accessRole === "owner" || $calendar->accessRole === "writer") {
                $userGoogleCalendar = $calendarIntegrationConfig->googleCalendars()
                    ->where('google_id', $calendar['id'])
                    ->first();

                if (!$userGoogleCalendar) {
                    $userGoogleCalendar = new UserGoogleCalendar();
                }

                $userGoogleCalendar->user_id = $calendarIntegrationConfig->user_id;
                $userGoogleCalendar->user_calendar_integration_config_id = $calendarIntegrationConfig->id;
                $userGoogleCalendar->google_id = $calendar['id'];
                $userGoogleCalendar->etag = $calendar['etag'];
                $userGoogleCalendar->collection_key = $calendar['collection_key'];
                $userGoogleCalendar->description = $calendar['description'];
                $userGoogleCalendar->summary = $calendar['summary'];
                $userGoogleCalendar->primary = $calendar['primary'] ?? false;
                $userGoogleCalendar->selected = $calendar['selected'] ?? false;
                $userGoogleCalendar->timezone = $calendar['timeZone'];
                $userGoogleCalendar->background_color = $calendar['backgroundColor'];
                $userGoogleCalendar->foreground_color = $calendar['foregroundColor'];
                $userGoogleCalendar->save();
            }
        }
        
        // Remove local calendars which are no longer present in gcal
        foreach ($calendarIntegrationConfig->googleCalendars as $localGoogleCalendar) {
            $found = false;
            foreach ($calendarList->items as $googleCalendar) {
                if (!$found) {
                    $found = $googleCalendar['id'] === $localGoogleCalendar->google_id;
                }
            }
            if (!$found) {
                $localGoogleCalendar->delete();
            }
        }

    }
}
