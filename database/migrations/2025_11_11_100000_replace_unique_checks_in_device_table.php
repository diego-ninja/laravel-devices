<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('idx_platform_advertising_id_unique');
            $table->dropUnique('idx_platform_device_id_unique');

            $table->unique(['platform', 'browser', 'browser_engine', 'advertising_id'], 'idx_platform_browser_advertising_id_unique');
            $table->unique(['platform', 'browser', 'browser_engine', 'device_id'], 'idx_platform_browser_device_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('idx_platform_browser_advertising_id_unique');
            $table->dropUnique('idx_platform_browser_device_id_unique');

            $table->unique(['platform', 'advertising_id'], 'idx_platform_advertising_id_unique');
            $table->unique(['platform', 'device_id'], 'idx_platform_device_id_unique');
        });
    }
};
