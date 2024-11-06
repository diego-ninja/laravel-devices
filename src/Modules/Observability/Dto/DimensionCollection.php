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


    public function valid(array $required, array $allowed): bool
    {
        return
            empty($this->hasInvalidDimensions($allowed)) &&
            empty($this->hasMissingDimensions($required));
    }

    public function names(): array
    {
        return array_map(fn(Dimension $dimension) => $dimension->name, $this->array());
    }

    private function hasInvalidDimensions(array $allowedDimensions): array
    {
        if (empty($allowedDimensions)) {
            return [];
        }

        return array_diff($this->names(), $allowedDimensions);
    }

    private function hasMissingDimensions(array $requiredDimensions): array
    {
        if (empty($requiredDimensions)) {
            return [];
        }

        return array_diff($requiredDimensions, $this->names());
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
