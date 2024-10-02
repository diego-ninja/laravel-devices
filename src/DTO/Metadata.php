<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;

final class Metadata implements JsonSerializable
{
    public function __construct(private array $data)
    {
    }

    public function __call($name, $arguments): mixed
    {
        if (str_starts_with($name, 'get')) {
            return $this->get(lcfirst(substr($name, 3)));
        }

        if (str_starts_with($name, 'set')) {
            $this->set(lcfirst(substr($name, 3)), $arguments[0]);
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function asArray(): array
    {
        return $this->data;
    }

    public static function from(array $data): self
    {
        return new self($data);
    }

    public function jsonSerialize(): array
    {
        return $this->asArray();
    }
}