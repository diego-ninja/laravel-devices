<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;
use Ninja\DeviceTracker\Modules\Observability\Metrics\Device\DeviceCount;

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
        array $dimensions,
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

        self::register(DeviceCount::create());
    }

    public static function get(MetricName $name): ?MetricDefinition
    {
        self::ensureInitialized();
        return self::$metrics->first(fn(MetricDefinition $metric) => $metric->name() === $name->value);
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
    public static function register(MetricDefinition $metric): void
    {
        self::ensureInitialized();
        self::$metrics->put($metric->name(), $metric);
    }

    private static function ensureInitialized(): void
    {
        if (!self::$initialized) {
            self::initialize();
        }
    }

    public static function asPrometheusConfig(): array
    {
        self::ensureInitialized();

        return self::$metrics->mapWithKeys(function (MetricDefinition $metric) {
            return [$metric->name() => [
                'type' => strtolower($metric->type()->value),
                'help' => $metric->description(),
                'labels' => $metric->labels(),
                'buckets' => $metric->buckets()
            ]];
        })->all();
    }
}
