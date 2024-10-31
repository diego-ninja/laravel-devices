<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Rule\Contracts\Rule;

abstract class AbstractSecurityRule implements Rule
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly float $weight,
        public readonly int $threshold,
        public readonly bool $enabled = true
    ) {
    }

    public static function from(array $data): self
    {
        return new static(
            $data['name'],
            $data['description'],
            $data['weight'],
            $data['threshold'],
            $data['enabled'] ?? true
        );
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function weight(): float
    {
        return $this->weight;
    }

    public function threshold(): int
    {
        return $this->threshold;
    }
}
