<?php

namespace Ninja\DeviceTracker\Modules\Location;

use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;

abstract class AbstractLocationProvider implements LocationProvider
{
    protected Location $location;
    abstract public function locate(string $ip): Location;

    public function country(): string
    {
        return $this->location->country;
    }

    public function region(): string
    {
        return $this->location->region;
    }

    public function city(): string
    {
        return $this->location->city;
    }

    public function postal(): string
    {
        return $this->location->postal;
    }

    public function latitude(): ?string
    {
        return $this->location->latitude;
    }

    public function longitude(): ?string
    {
        return $this->location->longitude;
    }

    public function timezone(): ?string
    {
        return $this->location->timezone;
    }

    public function location(): ?Location
    {
        return $this->location;
    }
}
