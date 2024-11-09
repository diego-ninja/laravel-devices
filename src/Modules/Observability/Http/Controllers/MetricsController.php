<?php

namespace Ninja\DeviceTracker\Modules\Observability\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\AggregateMetricExporter;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\RealtimeMetricExporter;

class MetricsController extends Controller
{
    public function aggregated(AggregateMetricExporter $exporter): Response
    {
        return $this->response($exporter->export());
    }

    public function realtime(RealtimeMetricExporter $exporter): Response
    {
        return $this->response($exporter->export());
    }

    private function response(string $metrics): Response
    {
        return response($metrics, 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }
}