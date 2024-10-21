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
        $property = $this->underscorize(substr($name, 3));

        if (str_starts_with($name, 'get')) {
            return $this->get($property);
        }

        if (str_starts_with($name, 'set')) {
            $this->set($property, $arguments[0]);
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$this->underscorize($key)] = $value;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function json(): string
    {
        return json_encode($this->data);
    }

    public static function from(array $data): self
    {
        return new self($data);
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }

    private function camelize(string $str): string
    {
        return str($str)->camel()->ucfirst();
    }

    private function underscorize(string $str): string
    {
        return str($str)->lower()->snake();
    }
}
