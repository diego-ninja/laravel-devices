<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_events', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('device_uuid');
            $table->string('session_uuid')->nullable();
            $table->string('type');
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->foreign('device_uuid')
                ->references('uuid')
                ->on('devices')
                ->onDelete('cascade');

            $table->foreign('session_uuid')
                ->references('uuid')
                ->on('device_sessions')
                ->onDelete('cascade');

            $table->index('device_uuid');
            $table->index('session_uuid');
            $table->index('type');
            $table->index('occurred_at');

            $table->index(['device_uuid', 'occurred_at']);
            $table->index(['session_uuid', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_events');
    }
};
