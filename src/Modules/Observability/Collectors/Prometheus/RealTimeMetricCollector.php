<?php

namespace Ninja\DeviceTracker\Modules\Observability\Collectors\Prometheus;

use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;

final readonly class RealTimeMetricCollector extends AbstractPrometheusCollector
{
    public function __construct(
        private MetricStorage $storage
    ) {
    }

    public function register(): void
    {
        foreach (MetricType::cases() as $type) {
            $pattern = sprintf('*:%s:*', $type->value);
            $keys = $this->storage->keys($pattern);

            foreach ($keys as $key) {
                $decodedKey = Key::decode($key);
                $value = $this->storage->value($key, $decodedKey->type);

                if (empty($value)) {
                    continue;
                }

                $this->metric(
                    name: $this->name($decodedKey->name->value),
                    type: $decodedKey->type,
                    value: $value[0]['value'],
                    labels: $this->labels($decodedKey->dimensions->toArray())
                );
            }
        }
    }
}
