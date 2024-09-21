<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid()->unique();
            $table->integer('user_id');
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
        });
    }

    public function down(): void
    {
        Schema::drop('devices');
    }
};
