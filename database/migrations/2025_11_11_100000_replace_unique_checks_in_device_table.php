<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace two unique constraints on the `devices` table with new composite unique constraints that include browser and browser_engine.
     *
     * Drops `idx_platform_advertising_id_unique` and `idx_platform_device_id_unique`, then creates
     * `idx_platform_browser_advertising_id_unique` on (platform, browser, browser_engine, advertising_id)
     * and `idx_platform_browser_device_id_unique` on (platform, browser, browser_engine, device_id).
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique('idx_platform_advertising_id_unique');
            $table->dropUnique('idx_platform_device_id_unique');

            $table->unique(['platform', 'browser', 'browser_engine', 'advertising_id'], 'idx_platform_browser_advertising_id_unique');
            $table->unique(['platform', 'browser', 'browser_engine', 'device_id'], 'idx_platform_browser_device_id_unique');
        });
    }

    /**
     * Reverts the schema changes to the devices table by restoring the original unique indexes.
     *
     * Drops the composite unique indexes that include browser and browser_engine and recreates the
     * original unique constraints on (platform, advertising_id) and (platform, device_id).
     */
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