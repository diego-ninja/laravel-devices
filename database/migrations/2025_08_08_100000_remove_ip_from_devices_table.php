<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('ip');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('ip')->nullable();
        });

        DB::statement('UPDATE devices SET ip = ds.ip FROM devices d JOIN device_sessions ds ON d."uuid" = ds.device_uuid;');
        DB::statement('UPDATE devices SET ip = \'127.0.0.1\' WHERE ip IS NULL;');

        Schema::table('devices', function (Blueprint $table) {
            $table->string('ip')->nullable(false)->change();
        });
    }
};
