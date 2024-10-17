<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->string($this->field());
            $table->string('device_uuid');
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
