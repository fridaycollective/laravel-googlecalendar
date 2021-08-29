<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGoogleCalendarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_google_calendars', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('user_calendar_integration_config_id');
            $table->string('google_id');
            $table->string('etag');
            $table->boolean('sync_enabled')->default(false);
            $table->string('collection_key');
            $table->string('description')->nullable();
            $table->string('summary');
            $table->boolean('primary')->default(false);
            $table->boolean('selected')->default(false);
            $table->string('timezone');
            $table->string('background_color');
            $table->string('foreground_color');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_google_calendars');
    }
}
