<?php

namespace Ninja\DeviceTracker\Modules\Observability\Metrics;

use Illuminate\Contracts\Support\Arrayable;
use Ninja\DeviceTracker\Modules\Observability\Dto\DimensionCollection;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricType;
use Ninja\DeviceTracker\Modules\Observability\Exceptions\InvalidMetricException;

abstract class AbstractMetricDefinition implements Arrayable
{
    private const DEFAULT_MIN_VALUE = -PHP_FLOAT_MAX;
    private const DEFAULT_MAX_VALUE = PHP_FLOAT_MAX;
    private array $buckets;
    private array $quantiles;
    private array $allowed_dimensions;

    public function __construct(
        private readonly MetricName $name,
        private readonly MetricType $type,
        private readonly string $description,
        private readonly string $unit = '',
        private readonly array $options = [],
        private readonly array $required_dimensions = [],
        array $allowed_dimensions = [],
        array $buckets = [],
        array $quantiles = [],
        private readonly ?float $min = null,
        private readonly ?float $max = null,
    ) {
        $this->allowed_dimensions = array_merge(config('devices.metrics.labels', []), $allowed_dimensions);
        $this->buckets = match ($type) {
            MetricType::Histogram => $buckets ?: config('devices.metrics.buckets', []),
            default => []
        };
        $this->quantiles = match ($type) {
            MetricType::Summary => $quantiles ?: [0.5, 0.9, 0.95, 0.99],
            default => []
        };
    }

    abstract public static function create(): self;

    /**
     * @throws InvalidMetricException
     */
    public function valid(
        MetricType $type,
        float $value,
        DimensionCollection $dimensions,
        bool $throwException = true
    ): bool {
        try {
            if ($type !== $this->type) {
                throw InvalidMetricException::invalidType($this->name, $this->type, $type);
            }

            $this->validateDimensions($dimensions);
            $this->validateValue($value);

            return true;
        } catch (InvalidMetricException $e) {
            if ($throwException) {
                throw $e;
            }
            return false;
        }
    }


    public function name(): string
    {
        return $this->name->value;
    }

    public function type(): MetricType
    {
        return $this->type;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function dimensions(): array
    {
        return $this->allowed_dimensions;
    }

    public function buckets(): array
    {
        return $this->buckets;
    }

    public function quantiles(): array
    {
        return $this->quantiles;
    }

    public function options(): array
    {
        return $this->options;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name->value,
            'type' => $this->type->value,
            'description' => $this->description,
            'unit' => $this->unit,
            'allowed_dimensions' => $this->allowed_dimensions,
            'required_dimensions' => $this->required_dimensions,
            'buckets' => $this->buckets,
            'quantiles' => $this->quantiles,
            'options' => $this->options,
            'min' => $this->min,
            'max' => $this->max,
        ];
    }

    /**
     * @throws InvalidMetricException
     */
    private function validateDimensions(DimensionCollection $dimensions): void
    {
        $allowed_dimensions = array_merge(
            $this->required_dimensions,
            $this->allowed_dimensions
        );

        if (!empty($allowed_dimensions)) {
            $invalidDimensions = array_diff(
                array_keys($dimensions->toArray()),
                $allowed_dimensions
            );

            if (!empty($invalidDimensions)) {
                throw InvalidMetricException::invalidDimensions($this->name, $invalidDimensions);
            }
        }

        if (!empty($this->required_dimensions)) {
            $missingDimensions = array_diff(
                $this->required_dimensions,
                array_keys($dimensions->toArray())
            );

            if (!empty($missingDimensions)) {
                throw InvalidMetricException::missingRequiredDimensions($this->name, $missingDimensions);
            }
        }
    }

    /**
     * @throws InvalidMetricException
     */
    private function validateValue(float $value): void
    {
        $min = $this->min ?? self::DEFAULT_MIN_VALUE;
        $max = $this->max ?? self::DEFAULT_MAX_VALUE;

        if ($value < $min || $value > $max) {
            throw InvalidMetricException::valueOutOfRange($this->name, $value, $min, $max);
        }
    }
}
