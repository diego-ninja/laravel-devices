<?php

namespace Ninja\DeviceTracker\Database\Seeders;

use Illuminate\Database\Seeder;

class DevicesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DeviceTrackerSeeder::class,
            DeviceEnrichmentSeeder::class,
        ]);
    }
}