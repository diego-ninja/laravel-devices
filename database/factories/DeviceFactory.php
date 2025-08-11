<?php

namespace Ninja\DeviceTracker\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Device;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'uuid' => DeviceIdFactory::from($this->faker->uuid),
            'advertising_id' => $this->faker->boolean() ? $this->faker->uuid : null,
            'device_id' => $this->faker->boolean() ? $this->faker->uuid : null,
        ];
    }
}
