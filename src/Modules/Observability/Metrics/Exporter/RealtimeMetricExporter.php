<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Exporter;

use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;

final readonly class RealtimeMetricExporter extends AbstractMetricExporter
{
    public function __construct(
        private MetricStorage $storage
    ) {
        parent::__construct();
    }
    protected function collect(): array
    {
        $metrics = [];

        foreach (MetricType::cases() as $type) {
            $pattern = sprintf('*:%s:*', $type->value);
            $keys = $this->storage->keys($pattern);

            foreach ($keys as $key) {
                $decodedKey = Key::decode($key);
                $value = $this->storage->value($key);

                $metrics[] = [
                    'name' => $this->name($decodedKey->name),
                    'type' => MetricType::Gauge->value,
                    'help' => "Real-time metric: {$decodedKey->name}",
                    'value' => $value->value(),
                    'labels' => [
                        ...$this->labels($decodedKey->dimensions),
                        'window' => $decodedKey->window->value
                    ]
                ];
            }
        }

        return $metrics;
    }
}
