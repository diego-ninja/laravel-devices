<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ninja\DeviceTracker\Enums\DeviceStatus;

return new class extends Migration {
    public function up(): void
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
    }

    public function down(): void
    {
        Schema::drop($this->table());
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
