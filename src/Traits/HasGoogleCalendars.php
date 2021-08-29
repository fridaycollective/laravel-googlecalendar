<?php

namespace FridayCollective\LaravelGoogleCalendar\Traits;

use FridayCollective\LaravelGoogleCalendar\Models\UserGoogleCalendar;

trait HasGoogleCalendars
{
    public function googleCalendars()
    {
        return $this->hasMany(UserGoogleCalendar::class);
    }
}
