<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('end_date')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->string('ip')->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('platform_version')->nullable();
            $table->tinyInteger('mobile')->nullable();
            $table->string('device')->nullable();
            $table->string('location')->nullable();
            $table->tinyInteger('robot')->nullable();
            $table->integer('user_id');
            $table->tinyInteger('block')->nullable();
            $table->integer('blocked_by')->nullable();
            $table->string('device_uid')->nullable();
            $table->string('login_code')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('device_sessions');
    }
};
