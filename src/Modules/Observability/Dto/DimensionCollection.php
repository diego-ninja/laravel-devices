<?php

namespace Ninja\DeviceTracker\Modules\Observability\Dto;

use Illuminate\Support\Collection;

final class DimensionCollection extends Collection implements \JsonSerializable, \Stringable
{
    public function array(): array
    {
        return $this->toArray();
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
            empty($this->invalidDimensions(array_merge($allowed, $required))) &&
            empty($this->missingDimensions($required));
    }

    public function names(): array
    {
        return array_map(fn(Dimension $dimension) => $dimension->name, $this->toArray());
    }

    public function invalidDimensions(array $allowedDimensions): array
    {
        if (empty($allowedDimensions)) {
            return [];
        }

        return array_diff($this->names(), $allowedDimensions);
    }

    private function missingDimensions(array $requiredDimensions): array
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
