<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Modules\Security\Rule\Contracts\Rule;

abstract class AbstractSecurityRule implements Rule
{
    public function __construct(
        protected string $name,
        protected string $description,
        protected float $weight,
        protected int $threshold,
        protected bool $enabled = true
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
}
