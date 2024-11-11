<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter\Metric;

final readonly class SummaryExporter extends AbstractMetricExporter
{
    public function export(): array
    {
        $metrics = [];
        $quantiles = $this->value["quantiles"];

        foreach ($quantiles as $quantile => $value) {
            $metrics[] = [
                'name' => $this->name,
                'type' => $this->type->value,
                'help' => sprintf("%s (quantile)", $this->help()),
                'value' => $value,
                'labels' => array_merge($this->labels(), ['quantile' => $quantile])
            ];
        }

        $metrics[] = [
            'name' => sprintf('%s_sum', $this->name),
            'type' => $this->type->value,
            'help' => sprintf("%s (sum)", $this->help()),
            'value' => $this->value["sum"],
            'labels' => $this->labels()
        ];

        $metrics[] = [
            'name' => sprintf('%s_count', $this->name),
            'type' => $this->type->value,
            'help' => sprintf("%s (count)", $this->help()),
            'value' => $this->value["count"],
            'labels' => $this->labels()
        ];

        return $metrics;
    }
}
