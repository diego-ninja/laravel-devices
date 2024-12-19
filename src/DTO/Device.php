<?php

namespace Ninja\DeviceTracker\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\Modules\Detection\DTO\Browser;
use Ninja\DeviceTracker\Modules\Detection\DTO\DeviceType;
use Ninja\DeviceTracker\Modules\Detection\DTO\Platform;
use Stringable;
use Zerotoprod\DataModel\DataModel;

final class Device implements JsonSerializable, Stringable
{
    use DataModel;

    public const UNKNOWN = 'UNK';

    public Browser $browser;

    public Platform $platform;

    public DeviceType $device;

    public ?string $grade = self::UNKNOWN;

    public ?string $source;

    public function unknown(): bool
    {
        return $this->browser->unknown() || $this->platform->unknown();
    }

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return [
            'browser' => $this->browser->array(),
            'platform' => $this->platform->array(),
            'device' => $this->device->array(),
            'grade' => $this->grade,
            'source' => $this->source,
            'label' => (string) $this,
        ];
    }

    public function __toString(): string
    {
        return sprintf('%s at %s on %s', $this->browser, $this->device, $this->platform);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function json(): string|false
    {
        return json_encode($this->array());
    }
}
