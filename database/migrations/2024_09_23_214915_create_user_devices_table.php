<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->bigInteger($this->field());
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

        Schema::table('devices', function (Blueprint $table) {
            $table->dropForeign('devices_user_id_foreign');
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::drop($this->table());
        Schema::table('', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
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
