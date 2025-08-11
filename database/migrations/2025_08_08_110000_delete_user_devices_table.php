<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\DeviceStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::drop($this->table());
    }

    public function down(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->bigInteger($this->field())->unsigned();
            $table->string('device_uuid');
            $table->string('status')->default(DeviceStatus::Unverified->value);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->foreign($this->field())
                ->references('id')
                ->on(config('devices.authenticatable_table'))
                ->onDelete('cascade');
            $table->foreign('device_uuid')
                ->references('uuid')
                ->on('devices')
                ->onDelete('cascade');
        });

        DB::statement(
            <<<'SQL'
            INSERT INTO customer_devices (customer_id, device_uuid, status, verified_at, last_activity_at, created_at, updated_at)
                SELECT user_id, device_uuid, MAX(d.status) AS status, MIN(verified_at) AS verified_at, MAX(last_activity_at) AS last_activity_at , MIN(created_at) AS created_at, MAX(updated_at) AS updated_at
                FROM device_sessions ds LEFT JOIN devices d ON ds.device_uuid = d."uuid"
                GROUP BY device_uuid, user_id;
            SQL
        );
    }

    private function table(): string
    {
        return sprintf('%s_devices', str(config('devices.authenticatable_table'))->singular());
    }

    private function field(): string
    {
        return sprintf('%s_id', str(config('devices.authenticatable_table'))->singular());
    }
};
