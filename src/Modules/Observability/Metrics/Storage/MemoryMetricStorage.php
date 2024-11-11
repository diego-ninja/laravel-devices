<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Modules\Observability\Contracts\MetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\AverageMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\CounterMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\GaugeMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\HistogramMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\PercentageMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\RateMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Dto\Value\SummaryMetricValue;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\MetricHandlerNotFoundException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Handlers\HandlerFactory;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Swoole\Table;
use Throwable;

final readonly class MemoryMetricStorage implements MetricStorage
{
    private Table $storage;
    private Table $index;

    public function __construct(
        private ?string $prefix = null,
        int $max = 10000
    ) {
        $this->storage = new Table($max);
        $this->storage->column('value', Table::TYPE_STRING, 1024);
        $this->storage->column('type', Table::TYPE_STRING, 32);
        $this->storage->column('timestamp', Table::TYPE_INT);
        $this->storage->column('expire_at', Table::TYPE_INT);
        $this->storage->create();

        $this->index = new Table($max);
        $this->storage->column('keys', Table::TYPE_STRING, 4096);
        $this->storage->column('expire_at', Table::TYPE_INT);
        $this->storage->create();
    }

    public function store(Key $key, MetricValue $value): void
    {
        try {
            $storageKey = $this->prefix($key);
            $timestamp = time();
            $expireAt = $timestamp + ($key->window->seconds() * 2);
            $indexKey = $this->getIndexKey($key->window, $key->type);

            match ($key->type) {
                MetricType::Counter => $this->storeCounter($storageKey, $value, $timestamp, $expireAt),
                default => $this->storeMetric($storageKey, $value, $key->type, $timestamp, $expireAt)
            };

            $this->updateIndex($indexKey, $storageKey, $expireAt);

        } catch (Throwable $e) {
            Log::error('Failed to store metric in memory', [
                'key' => (string)$key,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * @throws MetricHandlerNotFoundException
     */
    public function value(Key $key): MetricValue
    {
        $data = $this->storage->get($this->prefix($key));

        if (!$data || $data['expire_at'] < time()) {
            return $this->emptyValue($key->type);
        }

        $values = $this->extractValues($data);
        return HandlerFactory::compute($key->type, $values);
    }

    public function keys(string $pattern): array
    {
        $normalizedPattern = str_replace('*', '.*', $pattern);
        $keys = [];

        foreach ($this->storage as $key => $data) {
            if ($data['expire_at'] < time()) {
                $this->storage->del($key);
                continue;
            }

            if (preg_match("/$normalizedPattern/", $key)) {
                $keys[] = $this->strip($key);
            }
        }

        return $keys;
    }

    public function delete(TimeWindow|array $keys): void
    {
        if ($keys instanceof TimeWindow) {
            $keys = $this->keys($keys->key($this->prefix));
        }

        foreach ($keys as $key) {
            $this->storage->del($this->prefix($key));
        }
    }

    public function expired(TimeWindow $window): bool
    {
        $indexKey = $this->getIndexKey($window);
        $data = $this->index->get($indexKey);

        if (!$data || $data['expire_at'] < time()) {
            return true;
        }

        return empty(json_decode($data['keys'], true));
    }

    public function prune(Aggregation $window, Carbon $before): int
    {
        $count = 0;
        $beforeTimestamp = $before->timestamp;

        foreach ($this->storage as $key => $data) {
            if ($data['timestamp'] < $beforeTimestamp || $data['expire_at'] < time()) {
                $this->storage->del($key);
                $count++;
            }
        }

        return $count;
    }

    public function count(Aggregation $window): array
    {
        $counts = [];
        foreach (MetricType::cases() as $type) {
            $pattern = sprintf(
                '%s:*:%s:%s:*:*',
                $this->prefix,
                $type->value,
                $window->value
            );
            $counts[$type->value] = count($this->keys($pattern));
        }

        return [
            'total' => array_sum($counts),
            'by_type' => $counts
        ];
    }

    public function health(): array
    {
        return [
            'status' => 'healthy',
            'metrics_count' => iterator_count($this->storage),
            'memory' => [
                'size' => $this->storage->getSize(),
                'memory_size' => $this->storage->getMemorySize()
            ],
            'last_cleanup' => now()->toDateTimeString()
        ];
    }

    private function storeCounter(string $key, MetricValue $value, int $timestamp, int $expireAt): void
    {
        $current = $this->storage->get($key);
        $newValue = $current ?
            (float)json_decode($current['value'], true)['value'] + $value->value() :
            $value->value();

        $this->storage->set($key, [
            'value' => json_encode(['value' => $newValue]),
            'type' => MetricType::Counter->value,
            'timestamp' => $timestamp,
            'expire_at' => $expireAt
        ]);
    }

    private function storeMetric(string $key, MetricValue $value, MetricType $type, int $timestamp, int $expireAt): void
    {
        $this->storage->set($key, [
            'value' => json_encode([
                'value' => $value->value(),
                'timestamp' => $timestamp
            ]),
            'type' => $type->value,
            'timestamp' => $timestamp,
            'expire_at' => $expireAt
        ]);
    }

    private function updateIndex(string $indexKey, string $storageKey, int $expireAt): void
    {
        $current = $this->index->get($indexKey);
        $keys = $current ? json_decode($current['keys'], true) : [];

        if (!in_array($storageKey, $keys)) {
            $keys[] = $storageKey;
        }

        $this->index->set($indexKey, [
            'keys' => json_encode($keys),
            'expire_at' => $expireAt
        ]);
    }

    private function getIndexKey(TimeWindow|Aggregation $window, ?MetricType $type = null): string
    {
        if ($window instanceof TimeWindow) {
            return sprintf('index:%s:%d', $window->aggregation->value, $window->slot);
        }

        return $type ?
            sprintf('index:%s:%s', $window->value, $type->value) :
            sprintf('index:%s', $window->value);
    }

    private function extractValues(array $data): array
    {
        $decoded = json_decode($data['value'], true);
        return [
            [
                'value' => $decoded['value'],
                'timestamp' => $decoded['timestamp'] ?? $data['timestamp']
            ]
        ];
    }

    private function emptyValue(MetricType $type): MetricValue
    {
        return match ($type) {
            MetricType::Counter => CounterMetricValue::empty(),
            MetricType::Gauge => GaugeMetricValue::empty(),
            MetricType::Histogram => HistogramMetricValue::empty(),
            MetricType::Summary => SummaryMetricValue::empty(),
            MetricType::Average => AverageMetricValue::empty(),
            MetricType::Rate => RateMetricValue::empty(),
            MetricType::Percentage => PercentageMetricValue::empty(),
        };
    }

    private function prefix(string|Key $key): string
    {
        $key = $key instanceof Key ? (string)$key : $key;
        return sprintf('%s:%s', $this->prefix, $key);
    }

    private function strip(string $key): string
    {
        if (str_starts_with($key, $this->prefix . ':')) {
            return substr($key, strlen($this->prefix) + 1);
        }
        return $key;
    }
}
