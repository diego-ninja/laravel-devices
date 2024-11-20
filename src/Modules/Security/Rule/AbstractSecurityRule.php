<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\Rule\Contracts\Rule;

abstract class AbstractSecurityRule implements Rule
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $factor,
        public readonly float $weight,
        public readonly int $threshold,
        public readonly bool $enabled = true
    ) {}

    public static function from(array $data): self
    {
        return new static(
            $data['name'],
            $data['description'],
            $data['factor'],
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

    protected function device(): ?Device
    {
        $session = $this->session();

        return $session?->device;
    }

    protected function session(): ?Session
    {
        try {
            $session = Session::current();
        } catch (SessionNotFoundException $e) {
            Log::warning('Session not found', ['exception' => $e]);

            return null;
        }

        return $session;
    }
}
