<?php

namespace FridayCollective\LaravelGoogleCalendar\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGoogleCalendar extends Model
{
    use SoftDeletes;
    
    public function user(){
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function calendarIntegrationConfig()
    {
        return $this->belongsTo(UserCalendarIntegrationConfig::class);
    }
}
