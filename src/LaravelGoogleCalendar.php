<?php

namespace FridayCollective\LaravelGoogleCalendar;

use FridayCollective\LaravelGoogleCalendar\Exceptions\AuthException;
use Illuminate\Support\Facades\Config;

class LaravelGoogleCalendar extends GoogleCalendarConnection
{
    protected $service;

    public function __construct($integrationConfig)
    {
        $config = Config::get('googlecalendar');
        $config['redirect_url'] = env('GOOGLE_CALENDAR_REDIRECT_URI');
        $config['state'] = $integrationConfig->state_uuid;

        parent::__construct($config, $integrationConfig);
    }

    /**
     * Returns the Gmail user email
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function user()
    {
        return $this->config('email');
    }

    /**
     * Updates / sets the current userId for the service
     *
     * @return \Google_Service_Gmail_Profile
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function redirect()
    {
        return $this->createAuthUrl($this->prepareCalendarScopes());
    }

    public function prepareCalendarScopes()
    {

    }

    public function logout()
    {
        $this->revokeToken();
        $this->deleteAccessToken();
    }

}
