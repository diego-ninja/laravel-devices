<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Contracts;

use Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Proxy\ContentProxy;

interface Storage
{
    public function content(): ContentProxy;
    public function update(array $content): self;
    public function write(array $content): self;
    public function read(): self;
    public function get(string $key): mixed;
    public function put(string $key, mixed $value): void;
    public function has(string $key): bool;
}
