<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('advertising_id')->nullable()->unique('idx_advertising_id_unique');
            $table->string('device_id')->nullable()->unique('idx_device_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('advertising_id');
            $table->dropColumn('device_id');
        });
    }
};
