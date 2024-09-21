<?php

namespace Ninja\DeviceTracker\Contracts;

use Ninja\DeviceTracker\DTO\Location;

interface LocationProvider
{
    public function country(): string;
    public function region(): string;
    public function city(): string;
    public function postal(): string;
    public function latitude(): ?string;
    public function longitude(): ?string;
    public function timezone(): ?string;
    public function fetch(string $ip): Location;
}
