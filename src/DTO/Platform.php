<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Platform implements JsonSerializable, Stringable
{
    public function __construct(
        public string $name,
        public Version $version,
        public string $family
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            version: is_array($data['version']) ?
                Version::fromArray($data['version']) :
                Version::fromString($data['version']),
            family: $data['family']
        );
    }

    public function array(): array
    {
        return [
            "name" => $this->name,
            "version" => $this->version->array(),
            "family" => $this->family,
            "label" => (string) $this
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf("%s", $this->name);
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
}
