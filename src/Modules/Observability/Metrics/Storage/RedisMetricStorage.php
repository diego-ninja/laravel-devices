<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage;

use Carbon\Carbon;
use Exception;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
use Throwable;

final readonly class RedisMetricStorage implements MetricStorage
{
    private Connection $redis;

    public function __construct(private string $prefix, private string $connection = 'default')
    {
        $this->redis = Redis::connection($this->connection);
    }

    public function store(Key $key, MetricValue $value): void
    {
        $metricKey = $this->prefix($key);

        try {
            $this->redis->pipeline(function ($pipe) use ($key, $metricKey, $value) {
                $timestamp = now();

                match ($key->type) {
                    MetricType::Counter => $pipe->incrbyfloat($metricKey, $value->value()),
                    MetricType::Gauge => $pipe->set($metricKey, json_encode([
                        'value' => $value->value(),
                        'timestamp' => $timestamp
                    ])),
                    MetricType::Histogram,
                    MetricType::Summary,
                    MetricType::Average,
                    MetricType::Percentage,
                    MetricType::Rate => $pipe->zadd($metricKey, $timestamp->timestamp, json_encode([
                        'value' => $value->value(),
                        'timestamp' => $timestamp
                    ]))
                };

                $pipe->expire($metricKey, now()->add($key->window->retention())->timestamp);
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

    /**
     * @throws MetricHandlerNotFoundException
     */
    public function value(Key $key): MetricValue
    {
        $stored = $this->fetch($key);
        if (empty($stored)) {
            return $this->empty($key->type);
        }

        return HandlerFactory::compute($key->type, $stored);
    }

    private function fetch(Key $key): array
    {
        try {
            $metricKey = $this->prefix($key);

            return match ($key->type) {
                MetricType::Counter => [
                    ['value' => (float) ($this->redis->get($metricKey) ?: 0)]
                ],
                MetricType::Gauge => $this->gauge($metricKey),
                MetricType::Histogram,
                MetricType::Summary,
                MetricType::Average,
                MetricType::Rate,
                MetricType::Percentage => $this->timeseries($metricKey)
            };
        } catch (Throwable $e) {
            Log::error('Failed to fetch metric value', [
                'key' => (string)$key,
                'type' => $key->type->value,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function gauge(string $key): array
    {
        $value = $this->redis->get($key);
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        return [
            [
                'value' => (float)($decoded['value'] ?? 0),
                'timestamp' => $decoded['timestamp'] ?? time()
            ]
        ];
    }

    private function timeseries(string $key): array
    {
        $values = $this->redis->zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $value => $score) {
            $decoded = json_decode($value, true);
            $result[] = [
                'value' => (float)($decoded['value'] ?? 0),
                'timestamp' => (int)$score
            ];
        }

        return $result;
    }

    private function empty(MetricType $type): MetricValue
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

    public function keys(?string $pattern = null): array
    {
        if (empty($pattern)) {
            $pattern = sprintf('%s:*', $this->prefix);
        }

        $keys = $this->redis->keys($this->prefix($pattern)) ?: [];
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

        $this->redis->pipeline(function ($pipe) use ($metricKeys) {
            foreach ($metricKeys as $key) {
                $pipe->del($key);
            }
        });
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

    public function expired(TimeWindow $window): bool
    {
        return $window->to->lt(now());
    }

    public function prune(Aggregation $window, Carbon $before): int
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
            $info = $this->redis->info();
            $keyspace = $this->redis->info('keyspace');

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
        $values = $this->redis->zrange($key, 0, -1, ['WITHSCORES' => true]);
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
        $values = $this->redis->zrange($key, 0, -1, ['WITHSCORES' => true]);
        if (empty($values)) {
            return [];
        }

        return array_map(function ($value, $score) {
            $decoded = json_decode($value, true);
            return [
                'value' => (float) ($decoded['value'] ?? $value),
                'timestamp' => (int) $score
            ];
        }, array_keys($values), array_values($values));
    }

    private function prefix(string|Key $key): string
    {
        $key = $key instanceof Key ? (string) $key : $key;

        if (str_starts_with($key, sprintf("%s:", $this->prefix))) {
            return $key;
        }

        return sprintf('%s:%s', $this->prefix, $key);
    }

    private function strip(string $key): string
    {
        $redisPrefix = config('database.redis.options.prefix', '');

        if (str_starts_with($key, $redisPrefix)) {
            $key = substr($key, strlen($redisPrefix));
        }

        if (str_starts_with($key, $this->prefix . ':')) {
            return substr($key, strlen($this->prefix) + 1);
        }

        return $key;
    }
}
