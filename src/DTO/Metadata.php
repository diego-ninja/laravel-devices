<?php

namespace Ninja\DeviceTracker\DTO;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use JsonSerializable;

final class Metadata implements JsonSerializable
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        $property = $this->underscorize(substr($name, 3));

        if (str_starts_with($name, 'get')) {
            return $this->get($property);
        }

        if (str_starts_with($name, 'set')) {
            $this->set($property, $arguments[0]);

            return $this;
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }

    public function set(string $key, mixed $value): self
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function forget(string $key): self
    {
        Arr::forget($this->data, $key);

        return $this;
    }

    public function push(string $key, mixed $value): self
    {
        $array = $this->get($key, []);
        if (! is_array($array)) {
            throw new InvalidArgumentException(sprintf('Key %s is not an array', $key));
        }

        $array[] = $value;

        return $this->set($key, $array);
    }

    public function increment(string $key, int $amount = 1): self
    {
        $value = (int) $this->get($key, 0);

        return $this->set($key, $value + $amount);
    }

    public function decrement(string $key, int $amount = 1): self
    {
        return $this->increment($key, -$amount);
    }

    /**
     * @param  array<string, mixed>|self  $data
     */
    public function merge(array|self $data): self
    {
        if ($data instanceof self) {
            $data = $data->array();
        }

        $this->data = array_merge_recursive($this->data, $data);

        return $this;
    }

    /**
     * @param  array<string>  $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return Arr::only($this->data, $keys);
    }

    /**
     * @param  array<string>  $keys
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        return Arr::except($this->data, $keys);
    }

    public function filter(callable $callback): self
    {
        $this->data = array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);

        return $this;
    }

    public function transform(callable $callback): self
    {
        $this->data = array_map($callback, $this->data);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return $this->data;
    }

    public function json(): string|false
    {
        return json_encode($this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->array();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self($data);
    }

    public function empty(): bool
    {
        return count($this->data) === 0;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * @return array<mixed>
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    private function underscorize(string $str): string
    {
        return str($str)->lower()->snake();
    }

    public function offsetExists(string $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(string $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(string $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(string $offset): void
    {
        $this->forget($offset);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __unset(string $name): void
    {
        $this->forget($name);
    }
}
