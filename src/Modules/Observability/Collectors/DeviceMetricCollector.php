<?php

namespace Ninja\DeviceTracker\Modules\Observability\Collectors;

use DB;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Events\DeviceDeletedEvent;
use Ninja\DeviceTracker\Events\DeviceHijackedEvent;
use Ninja\DeviceTracker\Events\DeviceVerifiedEvent;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;

final readonly class DeviceMetricCollector
{
    public function all(): void
    {
        $this->base();
        $this->risk();
        $this->lifespan();
    }

    public function handleDeviceCreated(DeviceCreatedEvent $event): void
    {
        $this->base();
        rate(
            name: MetricName::DeviceCreationRate->value,
            dimensions: [
                'platform_family' => $event->device->platform_family,
                'device_type' => $event->device->device_type
            ]
        );
    }

    public function handleDeviceVerified(DeviceVerifiedEvent $event): void
    {
        $this->base();
        $this->risk();

        $verificationTime = $event->device->verified_at->diffInSeconds($event->device->created_at);
        $dimensions = [
            'platform_family' => $event->device->platform_family,
            'device_type' => $event->device->device_type
        ];

        average(
            name: MetricName::DeviceVerificationTime->value,
            value: $verificationTime,
            dimensions: $dimensions
        );

        summary(
            name: MetricName::DeviceVerificationLatency->value,
            value: $verificationTime,
            dimensions: $dimensions
        );

        rate(
            name: MetricName::DeviceVerificationRate->value,
            dimensions: $dimensions
        );
    }

    public function handleDeviceHijacked(DeviceHijackedEvent $event): void
    {
        $this->base();
        $this->risk();

        if ($event->device->created_at && $event->device->hijacked_at) {
            $lifespan = $event->device->hijacked_at->diffInDays($event->device->created_at);

            average(
                name: MetricName::DeviceLifespan->value,
                value: $lifespan,
                dimensions: [
                    'platform_family' => $event->device->platform_family,
                    'device_type' => $event->device->device_type,
                    'status' => DeviceStatus::Hijacked->value,
                ]
            );
        }
    }

    public function handleDeviceDeleted(DeviceDeletedEvent $event): void
    {
        if ($event->device->created_at) {
            $lifespan = now()->diffInDays($event->device->created_at);

            average(
                name: MetricName::DeviceLifespan->value,
                value: $lifespan,
                dimensions: [
                    'platform_family' => $event->device->platform_family,
                    'device_type' => $event->device->device_type,
                    'status' => $event->device->status->value,
                ]
            );
        }

        $this->base();
    }


    private function base(): void
    {
        $devices = Device::byStatus()->toArray();
        $total = array_sum($devices);
        $verified = $devices['verified'] ?? 0;
        $hijacked = $devices['hijacked'] ?? 0;

        gauge(MetricName::DeviceCount->value, $total);
        gauge(MetricName::VerifiedDeviceCount->value, $verified);
        gauge(MetricName::HijackedDeviceCount->value, $hijacked);

        if ($total > 0) {
            gauge(MetricName::VerifiedDeviceRate->value, $verified / $total * 100);
            gauge(MetricName::HijackedDeviceRate->value, $hijacked / $total * 100);
        }

        $this->distributions();
    }

    private function distributions(): void
    {
        $platforms = Device::select(
            [
                'platform_family',
                'platform_version',
                'status',
                DB::raw('count(*) as count')
            ]
        )
            ->groupBy('platform_family', 'platform_version', 'status')
            ->get();

        foreach ($platforms as $distribution) {
            $dimensions = [
                'platform_family' => $distribution->platform_family,
                'platform_version' => $distribution->platform_version,
                'status' => $distribution->status->value,
            ];

            counter(MetricName::DevicePlatformDistribution->value, $distribution->count, $dimensions);
        }

        $types = Device::select([
            'device_type',
            'device_family',
            'platform_family',
            DB::raw('count(*) as count')
        ])
            ->groupBy('device_type', 'device_family', 'platform_family')
            ->get();

        foreach ($types as $distribution) {
            $dimensions = [
                'device_type' => $distribution->device_type,
                'device_family' => $distribution->device_family,
                'platform_family' => $distribution->platform_family,
            ];

            counter(MetricName::DeviceTypeDistribution->value, $distribution->count, $dimensions);
        }
    }

    private function risk(): void
    {
        $scores = Device::whereNotNull('risk')
            ->select([
                'platform_family',
                'status',
                DB::raw('AVG(JSON_EXTRACT(risk, "$.score")) as avg_score')])
            ->groupBy('platform_family', 'status')
            ->get();

        foreach ($scores as $score) {
            $dimensions = [
                'platform_family' => $score->platform_family,
                'status' => $score->status->value,
            ];

            gauge(MetricName::DeviceRiskScore->value, $score->avg_score, $dimensions);
            summary(MetricName::DeviceRiskScoreDistribution->value, $scores->avg('avg_score'), $dimensions);
        }
    }

    private function lifespan(): void
    {
        $lifespansByStatus = Device::selectRaw('
            status,
            platform_family,
            device_type,
            AVG(DATEDIFF(COALESCE(hijacked_at, deleted_at, NOW()), created_at)) as avg_lifespan
        ')
            ->whereNotNull('created_at')
            ->groupBy('status', 'platform_family', 'device_type')
            ->get();

        foreach ($lifespansByStatus as $lifespan) {
            average(
                name: MetricName::DeviceLifespan->value,
                value: $lifespan->avg_lifespan,
                dimensions: [
                    'platform_family' => $lifespan->platform_family,
                    'device_type' => $lifespan->device_type,
                    'status' => $lifespan->status->value,
                ]
            );
        }
    }
}
