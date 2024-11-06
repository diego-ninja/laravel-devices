<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto;

final readonly class Dimension implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
    }

    public function array(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    public static function from(string|array|Dimension $data): self
    {
        if ($data instanceof self) {
            return $data;
        }

        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        return new self(
            name: $data['name'],
            value: $data['value'],
        );
    }

    public function json(): string
    {
        return json_encode($this->array());
    }
    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
