<?php

namespace FridayCollective\LaravelGoogleCalendar\Traits;

use FridayCollective\LaravelGoogleCalendar\Models\UserCalendarIntegrationConfig;

trait HasCalendarIntegrationConfig
{
    public function calendarIntegrationConfig()
    {
        return $this->hasOne(UserCalendarIntegrationConfig::class);
    }
}
