<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Dto;

use Carbon\Carbon;
use JsonSerializable;

class Pattern implements JsonSerializable
{
    public const MAX_RECORDS = 50;

    public function __construct(public array $timestamps = [], public array $intervals = [])
    {
    }

    public function add(Carbon $track): void
    {
        $this->timestamps[] = $track->timestamp;
        if (count($this->timestamps) === 1) {
            return;
        }

        $this->intervals[] = $track->diffInSeconds(end($this->timestamps));

        $this->timestamps = array_slice($this->timestamps, -self::MAX_RECORDS);
        $this->intervals = array_slice($this->intervals, -(self::MAX_RECORDS - 1));
    }

    public function density(): float
    {
        if (count($this->timestamps) < 2) {
            return 0;
        }

        $total = end($this->timestamps) - $this->timestamps[0];
        $tracks = count($this->timestamps);

        return $tracks / ($total / 86400);
    }

    public static function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self($data['timestamps'] ?? [], $data['intervals'] ?? []);
    }

    public function array(): array
    {
        return [
            'timestamps' => $this->timestamps,
            'intervals' => $this->intervals,
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
