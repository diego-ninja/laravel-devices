<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_session_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('session_id')->nullable();
            $table->timestamps();
            $table->string('route')->nullable();
            $table->text('uri')->nullable();
            $table->string('name')->nullable();
            $table->string('method')->nullable();
            $table->text('parameters')->nullable();
            $table->tinyInteger('type');
        });
    }

    public function down(): void
    {
        Schema::drop('device_session_request');
    }
};
