<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Ninja\DeviceTracker\Modules\Observability\Enums\Aggregation;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\Contracts\StateStorage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Storage\RedisMetricStorage;
use Ninja\DeviceTracker\Modules\Observability\ValueObjects\TimeWindow;

class StateManager
{
    private const PROCESSED_WINDOWS_KEY = 'processed_windows';
    private const LAST_PROCESSING_KEY = 'last_processing';
    private const ERROR_COUNT_KEY = 'processing_errors';

    public function __construct(
        private readonly StateStorage $storage,
        private string $prefix = '',
    ) {
        $this->prefix = $prefix ?: config('devices.observability.prefix');
    }

    public function success(TimeWindow $window): void
    {
        $this->storage->pipeline(function ($pipe) use ($window) {
            $pipe->set(
                $this->key(self::LAST_PROCESSING_KEY, $window->aggregation->value),
                $window->from->timestamp
            );

            $pipe->hset(
                $this->key(self::PROCESSED_WINDOWS_KEY, $window->aggregation->value),
                $this->getWindowIdentifier($window),
                json_encode([
                    'timestamp' => $window->from->timestamp,
                    'processed_at' => Carbon::now()->timestamp,
                    'window' => $window->aggregation->value,
                ])
            );
        });
    }

    public function wasSuccess(TimeWindow $window): bool
    {
        return $this->storage->hExists(
            $this->key(self::PROCESSED_WINDOWS_KEY, $window->aggregation->value),
            $this->getWindowIdentifier($window)
        );
    }

    public function error(Aggregation $window): void
    {
        $this->storage->increment($this->key(self::ERROR_COUNT_KEY, $window->value));
    }

    public function last(Aggregation $window): ?Carbon
    {
        $timestamp = $this->storage->get($this->key(self::LAST_PROCESSING_KEY, $window->value));
        return $timestamp ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function errors(Aggregation $window): int
    {
        return (int) $this->storage->get($this->key(self::ERROR_COUNT_KEY, $window->value)) ?? 0;
    }

    public function reset(Aggregation $window): void
    {
        $this->storage->delete($this->key(self::ERROR_COUNT_KEY, $window->value));
    }

    public function clean(Carbon $before): void
    {
        foreach (Aggregation::cases() as $window) {
            $processedWindows = $this->storage->hgetall(
                $this->key(self::PROCESSED_WINDOWS_KEY, $window->value)
            );

            foreach ($processedWindows as $windowId => $data) {
                $data = json_decode($data, true);
                if (Carbon::createFromTimestamp($data['timestamp'])->lt($before)) {
                    $this->storage->hdel(
                        $this->key(self::PROCESSED_WINDOWS_KEY, $window->value),
                        $windowId
                    );
                }
            }
        }
    }

    private function getWindowIdentifier(TimeWindow $window): string
    {
        return sprintf(
            '%s:%d',
            $window->aggregation->value,
            $window->slot
        );
    }

    private function key(string $type, string $window): string
    {
        return sprintf('%s:%s:%s', $this->prefix, $type, $window);
    }
}
