<?php

namespace FridayCollective\LaravelGoogleCalendar\Models;

use Illuminate\Database\Eloquent\Model;

class UserGoogleCalendar extends Model
{
    public function user(){
        return $this->belongsTo(config('auth.providers.users.model'));
    }
}
