<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;

class Registry
{
    private static bool $initialized = false;
    private static Collection $metrics;


    /**
     * @throws InvalidMetricException
     */
    public static function validate(
        MetricName $name,
        MetricType $type,
        float $value,
        DimensionCollection $dimensions,
        bool $throwException = true
    ): bool {
        self::ensureInitialized();

        $definition = self::get($name);
        if (!$definition) {
            if ($throwException) {
                throw new InvalidMetricException(sprintf('Metric %s not found in registry', $name->value));
            }
            return false;
        }

        return $definition->valid($type, $value, $dimensions, $throwException);
    }

    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$metrics = collect();
        foreach (config('devices.metrics.enabled') as $metric) {
            self::register($metric::create());
        }
        self::$initialized = true;
    }

    public static function get(MetricName $name): ?AbstractMetricDefinition
    {
        self::ensureInitialized();
        return self::$metrics->first(fn(AbstractMetricDefinition $metric) => $metric->name() === $name->value);
    }

    public static function all(): Collection
    {
        self::ensureInitialized();
        return self::$metrics;
    }
    public static function ofType(MetricType $type): Collection
    {
        self::ensureInitialized();
        return self::$metrics->filter(fn($metric) => $metric->getType() === $type);
    }
    public static function withLabel(string $label): Collection
    {
        self::ensureInitialized();
        return self::$metrics->filter(fn($metric) => in_array($label, $metric->getLabels()));
    }
    public static function register(AbstractMetricDefinition $metric): void
    {
        self::$metrics->put($metric->name(), $metric);
    }

    private static function ensureInitialized(): void
    {
        if (!self::$initialized) {
            self::initialize();
        }
    }
}
