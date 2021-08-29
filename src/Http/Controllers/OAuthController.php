<?php

namespace FridayCollective\LaravelGoogleCalendar\Http\Controllers;


use App\Models\CalendarIntegrationConfig;
use FridayCollective\LaravelGoogleCalendar\Models\UserGoogleCalendar;
use FridayCollective\LaravelGoogleCalendar\Services\Google\Calendar\GoogleCalendarService;
use FridayCollective\LaravelGoogleCalendar\LaravelGoogleCalendar;
use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function fetchCalendarIntegrationConfig()
    {
        $calendarIntegrationConfig = auth()->user()->calendarIntegrationConfig;
        if ($calendarIntegrationConfig && $calendarIntegrationConfig->status !== 'pending'){
            return $calendarIntegrationConfig;
        }
        return null;
    }

    public function googleCalendarRedirect()
    {
        $calendarIntegrationConfig = new UserCalendarIntegrationConfig();
        $calendarIntegrationConfig->user_id = auth()->user()->id;
        $calendarIntegrationConfig->type = 'google';
        $calendarIntegrationConfig->state_uuid = Str::uuid()->toString();
        $calendarIntegrationConfig->status = "pending";
        $calendarIntegrationConfig->save();

        $googleCalendarService = new LaravelGoogleCalendar($calendarIntegrationConfig);
        return $googleCalendarService->redirect();
    }

    public function googleCalendarCallback()
    {
        $error = Request::capture()->get('error');

        if (!$error) {
            $stateUuid = Request::capture()->get('state');
            $calendarIntegrationConfig = UserCalendarIntegrationConfig::where('state_uuid', $stateUuid)->first();

            $googleCalendarService = new LaravelGoogleCalendar($calendarIntegrationConfig);
            $googleCalendarService->makeToken();

            $calendarIntegrationConfig->status = "active";
            $calendarIntegrationConfig->save();

            $user = $calendarIntegrationConfig->user;
            $user->calendar_service = 'google';
            $user->save();

            UserCalendarIntegrationConfig::where('user_id', $calendarIntegrationConfig->user_id)
                ->where('status', 'pending')
                ->delete();

            /* DO THIS LATER PER CALENDAR INSTEAD AND REMOVE FROM HERE
            // Subscribe to calendar events
            $calendarService = new GoogleCalendarService($user->id);
            $calendarService->subscribeToCalendarNotifications();
            */
            
            $calendarService = new GoogleCalendarService($calendarIntegrationConfig);
            $calendarList = $calendarService->getCalendarList();

            foreach ($calendarList->items as $calendar) {
                if ($calendar->accessRole === "owner" || $calendar->accessRole === "writer") {
                    $userGoogleCalendar = new UserGoogleCalendar();
                    $userGoogleCalendar->user_id = $user->id;
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
        }

        return redirect()->to(env('PORTAL_URL') . '/settings/calendar-integration');
    }


    public function googleCalendarLogout()
    {
        try {
            $user = auth()->user();
            $calendarIntegrationConfig = $user->calendarIntegrationConfig;

            try {
                /* @TODO CHANGE THIS TO BE PER CALENDAR
                // Unsubscribe from calendar events
                $calendarService = new GoogleCalendarService($user->id);
                $calendarService->unsubscribeFromCalendarNotifications();
                 */
            } catch (\Exception $e) {
                Log::error($e);
            }

            $googleCalendarService = new LaravelGoogleCalendar($calendarIntegrationConfig);
            //$googleCalendarService->stop();
            $googleCalendarService->logout();

            UserGoogleCalendar::where('user_calendar_integration_config_id', $calendarIntegrationConfig->id)
                ->delete();

            UserCalendarIntegrationConfig::where('user_id', auth()->user()->id)
                ->where('type', 'google')
                ->delete();

            $user->calendar_service = null;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Disconnected.'], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['success' => false], 500);
        }
    }

}
