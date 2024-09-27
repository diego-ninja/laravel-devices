<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\SessionStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("google_2fa", function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->boolean('enabled')->nullable();

            $table->text('secret')
                ->after('enabled')
                ->nullable();

            $table->timestamp('last_success_at')
                ->after('secret')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop("google_2fa");
    }
};
