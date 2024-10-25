<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Storage;

use Illuminate\Support\Facades\File;

final class FileStorage extends AbstractStorage
{

    public function __construct(private readonly string $path)
    {
        if (!$this->persisted()) {
            $this->persist();
        }

        $this->read();
    }

    public function write(array $content): self
    {
        file_put_contents($this->path, json_encode($content, JSON_PRETTY_PRINT), LOCK_EX);
        //File::put($this->path, json_encode($content, JSON_PRETTY_PRINT));
        return $this;
    }

    public function read(): self
    {
        $this->update(json_decode(file_get_contents($this->path), true) ?? []);
        //$this->update(json_decode(File::get($this->path), true) ?? []);
        return $this;
    }

    protected function persisted(): bool
    {
        return file_exists($this->path);
        // return File::exists($this->path);
    }

    protected function persist(): self
    {
        return $this->write($this->content ?? []);
    }
}
