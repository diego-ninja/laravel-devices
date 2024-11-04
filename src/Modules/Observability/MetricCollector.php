<?php

namespace Ninja\DeviceTracker\Modules\Observability;

use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;

final readonly class MetricCollector
{
    public function __construct(
        private MetricAggregator $aggregator,
    ) {
    }

    public function handleDeviceCreated(DeviceCreatedEvent $event): void
    {
        $this->aggregator->counter(
            name: MetricName::DeviceUniqueCount,
            dimensions: [
                'platform' => $event->device->platform_family,
                'browser' => $event->device->browser_family,
            ]
        );

        /**
        $this->aggregator->counter(
            name: MetricName::DeviceOsDistribution,
            dimensions: [
                'platform' => $event->device->platform,
                'platform_family' => $event->device->platform_family,
                'platform_version' => $event->device->platform_version
            ]
        );

        $this->aggregator->counter(
            name: MetricName::DeviceBrowserDistribution,
            dimensions: [
                'browser' => $event->device->browser,
                'browser_family' => $event->device->browser_family,
                'browser_version' => $event->device->browser_version
            ]
        );
        **/
    }

}