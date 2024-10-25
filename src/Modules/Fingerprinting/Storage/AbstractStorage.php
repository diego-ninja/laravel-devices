<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Storage;

use Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Contracts\Storage;
use Ninja\DeviceTracker\Modules\Fingerprinting\Storage\Proxy\ContentProxy;

abstract class AbstractStorage implements Storage
{
    protected ContentProxy $proxy;

    protected array $content;

    public function content(): ContentProxy
    {
        return $this->proxy;
    }

    public function update(array $content): self
    {
        $this->content = $content;
        $this->proxy = $this->proxy($this->content);
        $this->write($content);

        return $this;
    }

    public function get(string $key): mixed
    {
        return $this->proxy->target[$key];
    }

    public function put(string $key, mixed $value): void
    {
        $this->proxy->target[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->proxy->target[$key]);
    }

    public function proxy(&$target): ContentProxy
    {
        return ContentProxy::for($target, $this);
    }


    abstract public function write(array $content): self;
    abstract public function read(): self;
    abstract protected function persist(): self;
    abstract protected function persisted(): bool;
}
