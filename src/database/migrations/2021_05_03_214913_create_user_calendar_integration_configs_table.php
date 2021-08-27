<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCalendarIntegrationConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_calendar_integration_configs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->json('config')->nullable();
            $table->string('type');
            $table->string('status');
            $table->string('sync_token')->nullable();
            $table->uuid('state_uuid')->nullable();
            $table->string('google_notification_channel_id')->nullable();
            $table->string('google_notification_resource_id')->nullable();
            $table->string('google_notification_channel_expiration')->nullable();
            $table->string('gmail_last_history_id')->nullable();
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
        Schema::dropIfExists('user_calendar_integration_configs');
    }
}
