<?php

use Ninja\DeviceTracker\Modules\Observability\Http\Controllers\MetricsController;

Route::prefix('metrics')->group(function () {
    Route::get('aggregated', [MetricsController::class, 'aggregated'])
        ->name('metrics.aggregated');

    Route::get('realtime', [MetricsController::class, 'realtime'])
        ->name('metrics.realtime');
})->middleware(['auth.basic']);
