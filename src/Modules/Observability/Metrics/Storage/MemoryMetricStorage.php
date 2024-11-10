<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Swoole\Table;
use Throwable;

class MemoryMetricStorage implements MetricStorage
{
    private readonly Table $storage;
    public function __construct(int $max = 10000, private readonly ?string $prefix = null)
    {
        $this->storage = new Table($max);
        $this->storage->column('value', Table::TYPE_STRING, 1024);
        $this->storage->column('timestamp', Table::TYPE_INT);
        $this->storage->column('expire_at', Table::TYPE_INT);
        $this->storage->create();
    }
    public function store(Key $key, float $value): void
    {
        try {
            $timestamp = time();
            $expireAt = $timestamp + ($key->window->seconds() * 2);
            $normalizedKey = $this->prefix($key);

            $storedValue = match ($key->type) {
                MetricType::Counter,
                MetricType::Gauge,
                MetricType::Average => (string) $value,
                MetricType::Rate,
                MetricType::Summary,
                MetricType::Histogram => json_encode([
                    'value' => $value,
                    'timestamp' => $timestamp
                ]),
            };

            $this->storage->set($normalizedKey, [
                'value' => $storedValue,
                'timestamp' => $timestamp,
                'expire_at' => $expireAt
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to store metric in memory', [
                'key' => (string)$key,
                'value' => $value,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function value(string $key, MetricType $type): array
    {
        $normalizedKey = $this->prefix($key);
        $data = $this->storage->get($normalizedKey);

        if (!$data || $data['expire_at'] < time()) {
            return [];
        }

        try {
            return match ($type) {
                MetricType::Counter,
                MetricType::Gauge,
                MetricType::Average => [[
                    'value' => (float)$data['value'],
                    'timestamp' => $data['timestamp']
                ]],
                MetricType::Rate,
                MetricType::Summary,
                MetricType::Histogram => [
                    json_decode($data['value'], true)
                ]
            };
        } catch (Throwable $e) {
            Log::error('Failed to get metric value from memory', [
                'key' => $key,
                'type' => $type->value,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    public function keys(string $pattern): array
    {
        $normalizedPattern = $this->prefix($pattern);
        $pattern = str_replace('*', '.*', $normalizedPattern);

        $keys = [];
        foreach ($this->storage as $key => $data) {
            if ($data['expire_at'] < time()) {
                $this->storage->del($key);
                continue;
            }

            if (preg_match("/$pattern/", $key)) {
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

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            $normalizedKey = $this->prefix($key);
            $this->storage->del($normalizedKey);
        }
    }

    public function expired(TimeWindow $window): bool
    {
        $pattern = sprintf(
            '%s:*:%s:%d:*',
            $this->prefix,
            $window->aggregation->value,
            $window->slot
        );

        return count($this->keys($pattern)) === 0;
    }

    public function prune(Aggregation $window, Carbon $before): int
    {
        $count = 0;
        $timestamp = $before->timestamp;

        foreach ($this->storage as $key => $data) {
            if ($data['timestamp'] < $timestamp) {
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

    private function prefix(string $key): string
    {
        if (str_starts_with($key, $this->prefix . ':')) {
            return $key;
        }

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
