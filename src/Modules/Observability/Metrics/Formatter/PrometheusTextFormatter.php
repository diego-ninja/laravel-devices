<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Formatter;

final readonly class PrometheusTextFormatter
{
    public function format(array $metrics): string
    {
        $output = [];

        foreach ($metrics as $metric) {
            $output[] = $this->help($metric);
            $output[] = $this->type($metric);
            $output[] = $this->value($metric);
        }

        return implode("\n", $output) . "\n";
    }

    private function help(array $metric): string
    {
        return sprintf(
            '# HELP %s %s',
            $metric['name'],
            $metric['help'] ?? "Device metric {$metric['name']}"
        );
    }

    private function type(array $metric): string
    {
        $type = match ($metric['type']) {
            'counter' => 'counter',
            'gauge' => 'gauge',
            'histogram' => 'histogram',
            'summary' => 'summary',
            default => 'untyped'
        };

        return sprintf('# TYPE %s %s', $metric['name'], $type);
    }

    private function value(array $metric): string
    {
        $labelPairs = [];
        foreach ($metric['labels'] as $label) {
            [$name, $value] = $label;
            $labelPairs[] = sprintf('%s="%s"', $name, $this->label($value));
        }

        $labels = empty($labelPairs) ? '' : '{' . implode(',', $labelPairs) . '}';

        return sprintf(
            '%s%s %s',
            $metric['name'],
            $labels,
            $this->number($metric['value'])
        );
    }

    private function label(string $value): string
    {
        return str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
    }

    private function number(float $value): string
    {
        if (is_infinite($value)) {
            return ($value > 0) ? '+Inf' : '-Inf';
        }
        if (is_nan($value)) {
            return 'NaN';
        }
        return sprintf('%.14g', $value);
    }
}
