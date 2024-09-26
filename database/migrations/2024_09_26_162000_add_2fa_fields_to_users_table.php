<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\SessionStatus;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(Config::get('devices.authenticatable_table'), function (Blueprint $table) {
            $table->text('two_factor_secret')
                ->after('password')
                ->nullable();

            $table->timestamp('two_factor_confirmed_at')
                ->after('two_factor_secret')
                ->nullable();
        });
    }

    public function down(): void
    {
    }
};
