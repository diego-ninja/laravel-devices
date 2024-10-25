<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tracking', function (Blueprint $table) {
            $table->id();
            $table->integer('storage_size')->default(-1);
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->string('path')->index();
            $table->string('route'); // page, route, favicon, etc.
            $table->string('type'); // page, route, favicon, etc.
            $table->string('title')->nullable();
            $table->integer('index')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tracking_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_tracking_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_tracking_at');
            $table->timestamp('last_tracking_at');
            $table->integer('count')->default(1);
            $table->json('pattern')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'tracking_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tracking');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('device_tracking_pages');
    }
};
