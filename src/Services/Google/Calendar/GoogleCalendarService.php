<?php

namespace FridayCollective\LaravelGoogleCalendar\Services\Google\Calendar;

use App\Models\User;
use Carbon\Carbon;
use DateTime;
use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;
use FridayCollective\LaravelGoogleCalendar\Models\UserGoogleCalendar;
use Google\Service\Exception;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\GoogleCalendar\GoogleCalendarFactory;

class GoogleCalendarService
{

    protected $client;
    protected $service;
    protected $file;
    protected $calendarConfig;

    public function __construct(UserCalendarIntegrationConfig $calendarIntegrationConfig)
    {
        $this->calendarConfig = $calendarIntegrationConfig;
        $allowJsonEncrypt = env('GOOGLE_ALLOW_JSON_ENCRYPT', false);
        if ($allowJsonEncrypt) {
            $accessToken = json_decode(decrypt($this->calendarConfig->config), true);
        } else {
            $accessToken = json_decode($this->calendarConfig->config, true);
        }

        $this->client = new Google_Client();
        $accessToken['client_id'] = config('gmail.client_id');
        $accessToken['client_secret'] = config('gmail.client_secret');
        $email = $accessToken['email'] ?? null;
        $this->client->setAuthConfig($accessToken);
        $this->client->setAccessToken($accessToken);
        if ($this->client->isAccessTokenExpired()) {
            Log::debug("EXPIRED: Access token expired for calendar config " . $calendarIntegrationConfig->id);
            $this->client->fetchAccessTokenWithRefreshToken($accessToken['refresh_token']);
            $accessToken = $this->client->getAccessToken();
            $accessToken['email'] = $email;
            $this->saveAccessToken($this->calendarConfig, $accessToken);
        } else {
            Log::debug("FOUND: Access token for calendar config " . $calendarIntegrationConfig->id);
        }
        $accessToken['client_id'] = config('gmail.client_id');
        $accessToken['client_secret'] = config('gmail.client_secret');
        $this->client->setAuthConfig($accessToken);
        $this->client->setAccessToken($accessToken);
        $this->service = new Google_Service_Calendar($this->client);
    }


    private function saveAccessToken(CalendarIntegrationConfig $dbCalendarConfig, array $config)
    {
        $allowJsonEncrypt = env('GOOGLE_ALLOW_JSON_ENCRYPT', false);
        if ($allowJsonEncrypt) {
            $accessToken = json_encode(encrypt($config), true);
        } else {
            $accessToken = json_encode($config, true);
        }

        $dbCalendarConfig->config = $accessToken;
        $dbCalendarConfig->save();
    }


    public function subscribeToCalendarNotifications(UserGoogleCalendar $userGoogleCalendar) {
        try {
            $channelConfig = new \Google_Service_Calendar_Channel();
            $channelConfig->setId('CRANK_CRM_U_GCAL_ID_' . $userGoogleCalendar->id);
            $channelConfig->setType('web_hook');
            $channelConfig->setAddress(env('APP_URL') . '/webhooks/google-calendar');

            $response = $this->service->events->watch($userGoogleCalendar->google_id, $channelConfig);

            $userGoogleCalendar->google_notification_channel_id = $response->getId();
            $userGoogleCalendar->google_notification_resource_id = $response->getResourceId();
            $userGoogleCalendar->google_notification_channel_expiration = $response->getExpiration();
            $userGoogleCalendar->save();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function unsubscribeFromCalendarNotifications(UserGoogleCalendar $userGoogleCalendar) {
        try {
            $channelConfig = new \Google_Service_Calendar_Channel();
            $channelConfig->setId($userGoogleCalendar->google_notification_channel_id);
            $channelConfig->setResourceId($userGoogleCalendar->google_notification_resource_id);

            $this->service->channels->stop($channelConfig);

            $userGoogleCalendar->google_notification_channel_id = null;
            $userGoogleCalendar->google_notification_resource_id = null;
            $userGoogleCalendar->google_notification_channel_expiration = null;
            $userGoogleCalendar->save();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }



    public function getCalendarList()
    {
        $calendarList = $this->service->calendarList->listCalendarList();

        return $calendarList;
    }


    /*
     * @link https://developers.google.com/google-apps/calendar/v3/reference/events/list
     */
    public function listEvents(User $user, Carbon $startDateTime = null, Carbon $endDateTime = null, array $queryParameters = [])
    {
        $calendarConfig = $user->calendarIntegrationConfig;
        $syncToken = $calendarConfig->sync_token;

        $retCollection = new Collection();
        $pageToken = null;
        $continue = true;

        while ($continue) {
            if ($calendarConfig->status === 'active') {
                try {
                    $defaultParams = ['singleEvents' => true,];

                    //if we have a sync token do an incremental sync otherwise do a full sync
                    if ($syncToken) {
                        $defaultParams['syncToken'] = $syncToken;
                    } else {
                        $startDateTime = $startDateTime ?? Carbon::now()->startOfDay();
                        $defaultParams['timeMin'] = $startDateTime->format(DateTime::RFC3339);
                        $endDateTime = $endDateTime ?? Carbon::now()->addYear()->endOfDay();
                        $defaultParams['timeMax'] = $endDateTime->format(DateTime::RFC3339);
                    }

                    $optParams = array_merge($defaultParams, $queryParameters);

                    if ($pageToken) {
                        $optParams['pageToken'] = $pageToken;
                    }
                    $eventResponse = $this->service->events->listEvents($this->calendarId, $optParams);
                    if (!$eventResponse) {
                        return null;
                    }


                    foreach ($eventResponse->getItems() as $event) {
                        $retCollection->push($event);
                    }

                    $pageToken = $eventResponse->nextPageToken;
                    $syncToken = $eventResponse->nextSyncToken;
                    $calendarConfig->sync_token = $syncToken;
                    $calendarConfig->save();

                    if (!$pageToken) {
                        $continue = false;
                    }
                } catch (Exception $e) {
                    if ($e->getCode() === 410) {
                        if (sizeof(array_filter($e->getErrors(), function ($error) {
                                return $error['reason'] === "fullSyncRequired";
                            })) > 0) {
                            Log::info("Refreshing sync token...");
                            $calendarConfig->sync_token = null;
                            $calendarConfig->save();
                            //try again
                            $syncToken = $calendarConfig->sync_token;
                            $pageToken = null;
                            $retCollection = new Collection();
                        } else {
                            //Log::error($e);
                        }
                    } else if ($e->getCode() === 404) {
                        if (sizeof(array_filter($e->getErrors(), function ($error) {
                                return $error['reason'] === "notFound";
                            })) > 0) {
                            Log::error("GOOGLE CALENDAR 404 NOT FOUND FOR USER " . $user->id);
                        } else {
                            //Log::error($e->getMessage());
                        }
                    } else if (strpos($e->getMessage(), 'Token has been expired or revoked') !== false) {
                        $calendarConfig->status = 'revoked_expired';
                        $calendarConfig->save();

                        Log::debug("Expired calendar integration for user: " . $user->id);
                    } else {
                        //Log::error($e);
                    }
                }
            }
        }


        return $retCollection;
    }

    public function insertEvent($event, $calendarId = 'primary')
    {
        return $this->service->events->insert($calendarId, $event);
    }


    public function deleteEvent($eventId, $calendarId = 'primary')
    {
        return $this->service->events->delete($calendarId, $eventId);
    }

    public function getEvent($eventId, $calendarId = 'primary')
    {
        try {
            return $this->service->events->get($calendarId, $eventId);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                if (sizeof(array_filter($e->getErrors(), function ($error) {
                        return $error['reason'] === "notFound";
                    })) > 0) {
                    Log::info("Google calendar event not found");
                } else {
                    Log::error($e);
                }
            }
        }
    }

    public function updateEvent($event, $calendarId = 'primary')
    {
        return $this->service->events->update('primary', $event->getId(), $event);
    }
}
