<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public const TABLE = 'device_metrics';
    public function up(): void
    {
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string(('metric_fingerprint'));
            $table->string('name');
            $table->string('type');
            $table->float('value');
            $table->json('dimensions');
            $table->timestamp('timestamp');
            $table->string('window');
            $table->timestamps();

            $table->unique('metric_fingerprint', 'device_metrics_unique');

            $table->index(['name', 'timestamp']);
            $table->index(['window', 'timestamp']);
            $table->index(['type', 'window']);
            $table->index(['name', 'type', 'window']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
