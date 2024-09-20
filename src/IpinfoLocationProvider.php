<?php

namespace Ninja\DeviceTracker;

use Ninja\DeviceTracker\DTO\Location;

class IpinfoLocationProvider implements Contracts\LocationProvider
{
    private const API_URL = "https://ipinfo.io/%s/json";

    private Location $location;

    public function __construct(string $ip)
    {
        $locationData = $this->fetch($ip);
        [$lat, $long] = explode(",", $locationData['loc']);

        $locationData['latitude'] = $lat;
        $locationData['longitude'] = $long;

        $this->location = Location::fromArray($locationData);
    }

    public function fetch(string $ip): array
    {
        $url = sprintf(self::API_URL, $ip);
        $response = file_get_contents($url);

        return json_decode($response, true);
    }

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
