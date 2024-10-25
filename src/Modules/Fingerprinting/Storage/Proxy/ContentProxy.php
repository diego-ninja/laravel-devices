<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Proxy;

use Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Contracts\Storage;

final class ContentProxy
{
    public function __construct(private array &$target, private readonly Storage $storage)
    {
    }

    public function __get(string $key)
    {
        if (is_array($this->target[$key]) || is_object($this->target[$key])) {
            return $this->storage->proxy($this->target[$key]);
        }
        return $this->target[$key];
    }

    public function __set(string $key, mixed $value)
    {
        $this->target[$key] = $value;
        $this->storage->write($this->storage->content()->target);
    }

    public static function for(array &$content, Storage $storage): self
    {
        return new self($content, $storage);
    }
}
