<?php

namespace Ninja\DeviceTracker\Modules\Detection\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\DTO\Device;
use Stringable;
use Zerotoprod\DataModel\DataModel;

final class DeviceType implements JsonSerializable, Stringable
{
    use DataModel;

    public string $family = Device::UNKNOWN;

    public string $model = Device::UNKNOWN;

    public string $type = Device::UNKNOWN;

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return [
            'family' => $this->family,
            'model' => $this->model,
            'type' => $this->type,
            'label' => (string) $this,
        ];
    }

    public function unknown(): bool
    {
        return
            in_array($this->family, [Device::UNKNOWN, '', null], true) &&
            in_array($this->model, [Device::UNKNOWN, '', null], true) &&
            in_array($this->type, [Device::UNKNOWN, '', null], true);
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
        return sprintf('%s %s (%s)', $this->family, $this->model, $this->type);
    }

    public function json(): string|false
    {
        return json_encode($this->array());
    }
}
