<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics\Providers;

use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\CreationRate;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DeviceCount;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DeviceTypeDistribution;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\HijackedDeviceCount;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\HijackedPercentage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\Lifespan;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DevicePlatformDistribution;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\RiskScoreAverage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DeviceRiskScoreDistribution;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\VerificationLatencyDistribution;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DeviceVerificationRate;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\DeviceVerificationTimeAverage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\VerifiedDeviceCount;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Definition\Device\VerifiedDevicePercentage;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Providers\Contracts\MetricProvider;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Registry;

class DeviceMetricProvider implements MetricProvider
{
    public const METRICS = [
        DeviceCount::class,
        VerifiedDeviceCount::class,
        HijackedDeviceCount::class,
        CreationRate::class,
        DeviceVerificationRate::class,
        VerificationLatencyDistribution::class,
        DeviceVerificationTimeAverage::class,
        VerifiedDevicePercentage::class,
        HijackedPercentage::class,
        DeviceTypeDistribution::class,
        DevicePlatformDistribution::class,
        RiskScoreAverage::class,
        DeviceRiskScoreDistribution::class,
        Lifespan::class
    ];

    public function register(): void
    {
        foreach (self::METRICS as $metric) {
            Registry::register($metric::create());
        }
    }
}
