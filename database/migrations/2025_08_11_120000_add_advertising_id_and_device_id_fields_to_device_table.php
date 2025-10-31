<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('advertising_id')->nullable();
            $table->string('device_id')->nullable();

            $table->unique(['platform', 'advertising_id'], 'idx_platform_advertising_id_unique');
            $table->unique(['platform', 'device_id'], 'idx_platform_device_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex('idx_platform_advertising_id_unique');
            $table->dropIndex('idx_platform_device_id_unique');

            $table->dropColumn('advertising_id');
            $table->dropColumn('device_id');
        });
    }
};
