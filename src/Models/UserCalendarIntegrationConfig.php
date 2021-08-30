<?php

namespace FridayCollective\LaravelGoogleCalendar\Models;

use Illuminate\Database\Eloquent\Model;

class UserCalendarIntegrationConfig extends Model
{
    protected $fillable = [
      "user_id",
      "config",
      "type",
      "status",
      "sync_to_user_google_calendar_id"
    ];

    public function user(){
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function googleCalendars()
    {
        return $this->hasMany(UserGoogleCalendar::class);
    }

    public function syncToGoogleCalendar()
    {
        return $this->belongsTo(UserGoogleCalendar::class, 'sync_to_user_google_calendar_id');
    }
}
