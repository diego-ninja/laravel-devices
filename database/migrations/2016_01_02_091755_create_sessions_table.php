<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid()->unique();
            $table->integer('user_id');
            $table->uuid('device_uuid');
            $table->string('ip')->nullable();
            $table->json('location')->nullable();
            $table->string('status')->default('active');
            $table->integer('blocked_by')->nullable();
            $table->string('login_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::drop('device_sessions');
    }
};
