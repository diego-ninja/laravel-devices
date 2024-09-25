<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class Version implements JsonSerializable, Stringable
{
    public function __construct(
        public string $major,
        public string $minor,
        public string $patch,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            major: $data['major'],
            minor: $data['minor'],
            patch: $data['patch']
        );
    }

    public function array(): array
    {
        return [
            "major" => $this->major,
            "minor" => $this->minor,
            "patch" => $this->patch,
            "label" => (string) $this
        ];
    }

    public function jsonSerialize(): array
    {

        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf("%s.%s.%s", $this->major, $this->minor, $this->patch);
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
}
