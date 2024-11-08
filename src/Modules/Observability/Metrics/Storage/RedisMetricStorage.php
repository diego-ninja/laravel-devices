<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Dto\Key;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\MetricStorage;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;
use Throwable;

final readonly class RedisMetricStorage implements MetricStorage
{
    public function __construct(private string $prefix)
    {
    }

    public function store(Key $key, float $value): void
    {
        $metricKey = $this->prefix((string) $key);

        try {
            Redis::pipeline(function ($pipe) use ($key, $metricKey, $value) {
                $timestamp = now();

                match ($key->type) {
                    MetricType::Counter => $pipe->incrbyfloat($metricKey, $value),
                    MetricType::Gauge => $pipe->set($metricKey, $value),
                    MetricType::Histogram,
                    MetricType::Summary,
                    MetricType::Rate => $pipe->zadd($metricKey, $timestamp->timestamp, json_encode([
                        'value' => $value,
                        'timestamp' => $timestamp
                    ])),
                    MetricType::Average => $pipe->zadd($metricKey, $timestamp->timestamp, $value)
                };

                $pipe->expire($metricKey, $key->window->seconds() * 2);
            });
        } catch (Throwable $e) {
            Log::error('Failed to store metric', [
                'key' => $metricKey,
                'value' => $value,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function value(string $key, MetricType $type): array
    {
        $metricKey = $this->prefix($key);
        try {
            return match ($type) {
                MetricType::Counter,
                MetricType::Gauge => [['value' => (float) Redis::get($metricKey)]],
                MetricType::Histogram,
                MetricType::Average => $this->histogram($metricKey),
                MetricType::Summary,
                MetricType::Rate => $this->timestamped($metricKey)
            };
        } catch (Throwable $e) {
            Log::error('Failed to get metric value', [
                'key' => $key,
                'type' => $type->value,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function keys(?string $pattern = null): array
    {
        if (empty($pattern)) {
            $pattern = sprintf('%s:*', $this->prefix);
        }

        $keys = Redis::keys($this->prefix($pattern)) ?: [];
        return array_map(fn($key) => $this->strip($key), $keys);
    }

    public function delete(TimeWindow|array $keys): void
    {
        if ($keys instanceof TimeWindow) {
            $keys = $this->keys($keys->key($this->prefix));
        }

        if (empty($keys)) {
            return;
        }

        $metricKeys = array_map(fn($key) => $this->prefix($key), $keys);

        Redis::pipeline(function ($pipe) use ($metricKeys) {
            foreach ($metricKeys as $key) {
                $pipe->del($key);
            }
        });
    }

    public function count(AggregationWindow $window): array
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

    public function expired(TimeWindow $window): bool
    {
        return $window->to->lt(now());
    }

    public function prune(AggregationWindow $window, Carbon $before): int
    {
        $pattern = sprintf(
            '%s:*:%s:%d:*',
            $this->prefix,
            $window->value,
            $before->timestamp
        );

        $keys = $this->keys($pattern);
        $count = count($keys);

        if ($count > 0) {
            $this->delete($keys);
        }

        return $count;
    }

    public function health(): array
    {
        try {
            $info = Redis::info();
            $keyspace = Redis::info('keyspace');

            return [
                'status' => 'healthy',
                'used_memory' => $info['used_memory_human'],
                'total_keys' => array_sum(array_map(
                    fn($db) => $db['keys'],
                    $keyspace
                )),
                'metrics_keys' => count($this->keys(
                    sprintf('%s:*', $this->prefix)
                )),
                'connected_clients' => $info['connected_clients'],
                'last_save_time' => Carbon::createFromTimestamp(
                    $info['rdb_last_save_time']
                )->toDateTimeString(),
                'operations' => [
                    'total_commands_processed' => $info['total_commands_processed'],
                    'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'],
                    'rejected_connections' => $info['rejected_connections'],
                ],
                'memory' => [
                    'used_memory' => $info['used_memory'],
                    'used_memory_peak' => $info['used_memory_peak'],
                    'memory_fragmentation_ratio' => $info['mem_fragmentation_ratio'],
                ]
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'last_check' => now()->toDateTimeString()
            ];
        }
    }

    private function histogram(string $key): array
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        return array_map(fn($value, $score) => [
            'value' => (float)$value,
            'timestamp' => (int)$score
        ], array_keys($values), array_values($values));
    }

    private function timestamped(string $key): array
    {
        $values = Redis::zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        return array_map(function ($value, $score) {
            $decoded = json_decode($value, true);
            return [
                'value' => (float)($decoded['value'] ?? $value),
                'timestamp' => (int)$score
            ];
        }, array_keys($values), array_values($values));
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
