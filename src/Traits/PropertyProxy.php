<?php

namespace Ninja\DeviceTracker\Traits;

trait PropertyProxy
{
    public function __call($method, $parameters): mixed
    {
        $property = $this->extract($method);

        if ($this->getter($method)) {
            if ($this->metadata->has($property)) {
                return $this->metadata->get($property);
            }
        }

        if ($this->setter($method)) {
            $this->metadata->set($property, $parameters[0]);
            return $this;
        }

        return parent::__call($method, $parameters);
    }

    public function getter(string $method): bool
    {
        if (str_starts_with($method, 'get')) {
            return true;
        }

        return false;
    }

    public function setter(string $method): bool
    {
        if (str_starts_with($method, 'set')) {
            return true;
        }

        return false;
    }

    private function extract(string $method): string
    {
        return str()->snake(substr($method, 3));
    }

    public function has(string $property): bool
    {
        return property_exists($this, $property);
    }
}
