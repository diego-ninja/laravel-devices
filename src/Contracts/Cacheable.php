<?php

namespace Ninja\DeviceTracker\Contracts;

interface Cacheable
{
    public function key(): string;

    public function ttl(): ?int;
}
