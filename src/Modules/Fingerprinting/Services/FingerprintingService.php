<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Services;

use Cache;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Events\TrackingIdentifiedEvent;
use Ninja\DeviceTracker\Modules\Fingerprinting\Jobs\ProcessTrackingVector;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Point;
use Swoole\Table;

final class FingerprintingService
{
    public const TRACKING_POINTS = 32;
    private ?Table $table = null;

    public function __construct()
    {
        if (app()->runningWithOctane()) {
            $this->init();
        }
    }

    public function identify(Device $device): ?string
    {
        if ($this->table && $cached = $this->table->get($device->uuid)) {
            return $cached['vector'];
        }

        $key = sprintf('tracking:vector:{%s}', $device->uuid);
        return Cache::remember($key, now()->addDay(), fn() => $this->rebuild($device));
    }

    public function track(StorableId $device_uuid, array $points): void
    {
        $vector = $this->vector($points);
        ProcessTrackingVector::dispatch($device_uuid, $vector);
        $this->table->set(
            $device_uuid->toString(),
            [
                'device_uuid' => (string) $device_uuid,
                'vector' => $vector,
                'expires' => now()->addDay()->timestamp
            ]
        );

        event(new TrackingIdentifiedEvent($device_uuid, $vector));
    }

    private function vector(array $routes): ?string
    {
        $bits = array_fill(0, self::TRACKING_POINTS, 0);
        foreach ($routes as $route) {
            $point = Point::byPath($route);
            $bits[$point->index] = 1;
        }

        return bindec(implode('', $bits));
    }

    private function rebuild(Device $device): ?string
    {
        if (!$device->tracking) {
            return null;
        }

        $bits = array_fill(0, self::TRACKING_POINTS, 0);
        foreach ($device->tracking->points as $point) {
            $bits[$point->index] = 1;
        }

        return bindec(implode('', $bits));
    }

    private function init(): void
    {
        $this->table = new Table(1024 * 1024);
        $this->table->column('device_uuid', Table::TYPE_STRING, 36);
        $this->table->column('vector', Table::TYPE_STRING, 1024);
        $this->table->column('expires', Table::TYPE_INT, 4);
        $this->table->create();
    }
}
