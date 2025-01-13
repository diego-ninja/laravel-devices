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
            'ip' => $this->faker->ipv4(),
        ];
    }
}
