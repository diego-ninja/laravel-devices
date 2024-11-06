<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto;

use Illuminate\Support\Collection;

final class DimensionCollection extends Collection implements \JsonSerializable, \Stringable
{
    public function array(): array
    {
        return $this->map->array()->all();
    }

    public static function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode(base64_decode($data), true);
        }

        if (empty($data)) {
            return new self();
        }

        return new self(
            array_map(fn($dimension) => Dimension::from($dimension), $data),
        );
    }
    public function json(): string
    {
        return json_encode($this->array());
    }

    public function __toString(): string
    {
        return base64_encode($this->json());
    }
}
