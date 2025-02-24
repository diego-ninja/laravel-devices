<?php

namespace Ninja\DeviceTracker\Modules\Security\Rules;

use Ninja\DeviceTracker\Modules\Security\Contracts\RuleInterface;

abstract readonly class AbstractRule implements RuleInterface
{
    public function __construct(
        public string $name,
        public float $weight,
        /** @var array<float> $thresholds */
        public array $thresholds,
        public bool $enabled = true,
        public ?string $description = null,
    ) {}

    public static function from(array $data): self
    {
        return new static(
            name: $data['name'],
            weight: $data['weight'],
            thresholds: $data['thresholds'],
            enabled: $data['enabled'] ?? true,
            description: $data['description'],
        );
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    public function thresholds(): array
    {
        return $this->thresholds;
    }
}
