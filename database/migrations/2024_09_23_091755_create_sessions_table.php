<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\SessionStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid')->unique();
            $table->integer('user_id');
            $table->string('device_uuid');
            $table->string('ip')->nullable();
            $table->json('location')->nullable();
            $table->string('status')->default(SessionStatus::Active->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->integer('blocked_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('device_sessions');
    }
};
