<?php

namespace Ninja\DeviceTracker\Modules\Detection\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\DTO\Device;
use Stringable;
use Zerotoprod\DataModel\DataModel;

final class Platform implements JsonSerializable, Stringable
{
    use DataModel;

    public string $name = Device::UNKNOWN;

    public ?Version $version = null;

    public string $family = Device::UNKNOWN;

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version?->array(),
            'family' => $this->family,
            'label' => (string) $this,
        ];
    }

    public function unknown(): bool
    {
        return
            in_array($this->name, [Device::UNKNOWN, '', null], true) &&
            in_array($this->family, [Device::UNKNOWN, '', null], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf('%s', $this->name);
    }

    public function json(): string|false
    {
        return json_encode($this->array());
    }
}
