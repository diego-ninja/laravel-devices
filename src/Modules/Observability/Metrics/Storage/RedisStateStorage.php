<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Enums\AggregationWindow;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\StateStorage;
use RuntimeException;
use Throwable;

final readonly class RedisStateStorage implements StateStorage
{
    private string $prefix;

    public function __construct(?string $prefix = null)
    {
        $this->prefix = $prefix ?: config('devices.metrics.aggregation.prefix');
    }
    public function get(string $key): ?string
    {
        return Redis::get($this->prefix($key));
    }

    public function set(string $key, string $value, ?int $ttl = null): void
    {
        $key = $this->prefix($key);

        if ($ttl) {
            Redis::setex($key, $ttl, $value);
        } else {
            Redis::set($key, $value);
        }
    }

    public function increment(string $key): int
    {
        return (int) Redis::incr($this->prefix($key));
    }

    public function delete(string $key): void
    {
        Redis::del($this->prefix($key));
    }

    public function hSet(string $key, string $field, string $value): void
    {
        Redis::hset($this->prefix($key), $field, $value);
    }

    public function hGet(string $key, string $field): ?string
    {
        return Redis::hget($this->prefix($key), $field);
    }

    public function hExists(string $key, string $field): bool
    {
        return (bool) Redis::hexists($this->prefix($key), $field);
    }

    public function hGetAll(string $key): array
    {
        return Redis::hgetall($this->prefix($key)) ?: [];
    }

    public function hDel(string $key, string $field): void
    {
        Redis::hdel($this->prefix($key), $field);
    }
    public function clean(): int
    {
        return 0;
    }

    public function state(AggregationWindow $window): array
    {
        $pattern = $this->prefix(sprintf('window:%s:*', $window->value));
        $keys = Redis::keys($pattern);

        $state = [];

        foreach ($keys as $key => $data) {
            $$key = $this->strip($key);
            $windowKey = str_replace(sprintf("window:%s:", $window->value), '', $key);
            $state[$windowKey] = Redis::get($key);
        }

        return $state;
    }

    public function pipeline(callable $callback): array
    {
        return Redis::pipeline($callback);
    }

    public function batch(array $operations): void
    {
        $this->pipeline(function ($pipe) use ($operations) {
            foreach ($operations as $operation) {
                [$method, $args] = $operation;
                $normalizedKey = $this->prefix($args[0]);
                $args[0] = $normalizedKey;
                $pipe->$method(...$args);
            }
        });
    }

    /**
     * Lock para operaciones que requieren exclusión mutua
     */
    public function lock(string $key, int $timeout = 10): bool
    {
        $lockKey = $this->prefix(sprintf('lock:%s', $key));
        return (bool) Redis::set($lockKey, '1', 'NX', 'EX', $timeout);
    }

    /**
     * Release lock
     */
    public function release(string $key): void
    {
        $lockKey = $this->prefix(sprintf('lock:%s', $key));
        Redis::del($lockKey);
    }

    public function withLock(string $key, callable $callback, int $timeout = 10): mixed
    {
        try {
            if (!$this->lock($key, $timeout)) {
                throw new RuntimeException(sprintf('Failed to acquire lock for key: %s', $key));
            }

            return $callback();
        } finally {
            $this->release($key);
        }
    }

    public function scan(string $pattern, int $count = 100): \Generator
    {
        $cursor = '0';
        $normalizedPattern = $this->prefix($pattern);

        do {
            [$cursor, $keys] = Redis::scan($cursor, [
                'match' => $normalizedPattern,
                'count' => $count
            ]);

            foreach ($keys as $key) {
                yield $this->strip($key);
            }
        } while ($cursor !== '0');
    }

    /**
     * Health check
     */
    public function health(): array
    {
        try {
            $info = Redis::info();

            return [
                'status' => 'healthy',
                'used_memory' => $info['used_memory_human'],
                'connected_clients' => $info['connected_clients'],
                'last_save_time' => Carbon::createFromTimestamp($info['rdb_last_save_time']),
                'total_connections_received' => $info['total_connections_received'],
                'total_commands_processed' => $info['total_commands_processed'],
                'instantaneous_ops_per_sec' => $info['instantaneous_ops_per_sec'],
                'expired_keys' => $info['expired_keys'],
                'evicted_keys' => $info['evicted_keys'],
                'keyspace_hits' => $info['keyspace_hits'],
                'keyspace_misses' => $info['keyspace_misses'],
                'memory_fragmentation_ratio' => $info['mem_fragmentation_ratio']
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()
            ];
        }
    }

    private function prefix(string $key): string
    {
        if (str_starts_with($key, $this->prefix . ':')) {
            return $key;
        }

        return sprintf('%s:state:%s', $this->prefix, $key);
    }

    private function strip(string $key): string
    {
        if (str_starts_with($key, $this->prefix . '%s:state')) {
            return substr($key, strlen($this->prefix) + 1);
        }

        return $key;
    }
}
