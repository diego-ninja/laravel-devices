<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\DeviceStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid()->unique();
            $table->integer('user_id');
            $table->string('status')->default(DeviceStatus::Unverified->value);
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();
            $table->tinyInteger('mobile')->nullable();
            $table->string('device')->nullable();
            $table->string('device_type')->nullable();
            $table->tinyInteger('robot')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('hijacked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('devices');
    }
};
