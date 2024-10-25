<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Storage;

use Illuminate\Support\Facades\Redis;
use RedisException;

final class RedisStorage extends AbstractStorage
{
    public function __construct(private string $key)
    {
        if (!$this->persisted()) {
            $this->persist();
        }

        $this->read();
    }

    public function write(array $content): self
    {
        Redis::client()->set($this->key, json_encode($content));

        return $this;
    }

    /**
     * @throws RedisException
     */
    public function read(): self
    {
        $this->update(json_decode(Redis::client()->get($this->key), true) ?? []);

        return $this;
    }

    public function persist(): self
    {
        return $this->write($this->content ?? []);
    }

    public function persisted(): bool
    {
        return Redis::client()->exists($this->key);
    }
}
