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
    ];

    public function user(){
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function googleCalendars()
    {
        return $this->hasMany(UserGoogleCalendar::class);
    }
}
