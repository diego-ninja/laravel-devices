<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('device', function (Blueprint $table) {
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->json('risk_level')->default('{"score": 0,"level": "low"}');
            $table->timestamp('risk_assessed_at')->nullable();

            $table->index('risk_score');
            $table->index('risk_assessed_at');

            $table->index(['risk_score', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('device', function (Blueprint $table) {
            $table->dropColumn('risk_score');
            $table->dropColumn('risk_level');
            $table->dropColumn('risk_assessed_at');
        });
    }
};
