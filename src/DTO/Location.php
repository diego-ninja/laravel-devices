<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Location implements JsonSerializable, Stringable
{
    public function __construct(
        public ?string $ip,
        public ?string $hostname,
        public ?string $country,
        public ?string $region,
        public ?string $city,
        public ?string $postal,
        public ?string $latitude,
        public ?string $longitude,
        public ?string $timezone
    ) {
    }

    public static function fromArray(array $location): self
    {
        return new self(
            ip: $location['ip'] ?? null,
            hostname: $location['hostname'] ?? null,
            country: $location['country'] ?? null,
            region: $location['region'] ?? null,
            city: $location['city'] ?? null,
            postal: $location['postal'] ?? null,
            latitude: $location['latitude'] ?? null,
            longitude: $location['longitude'] ?? null,
            timezone: $location['timezone'] ?? null
        );
    }

    public function array(): array
    {
        return [
            "ip" => $this->ip,
            "hostname" => $this->hostname,
            "country" => $this->country,
            "region" => $this->region,
            "city" => $this->city,
            "postal" => $this->postal,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "timezone" => $this->timezone
        ];
    }

    public function __toString()
    {
        return sprintf("%s %s, %s, %s", $this->postal, $this->city, $this->region, $this->country);
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
}
