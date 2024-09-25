<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Stringable;

final readonly class DeviceType implements JsonSerializable, Stringable
{
    public function __construct(
        public string $family,
        public string $model,
        public string $type
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            family: $data['family'],
            model: $data['model'],
            type: $data['type']
        );
    }

    public function array(): array
    {
        return [
            "family" => $this->family,
            "model" => $this->model,
            "type" => $this->type,
            "label" => (string) $this
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf("%s %s (%s)", $this->family, $this->model, $this->type);
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
}
