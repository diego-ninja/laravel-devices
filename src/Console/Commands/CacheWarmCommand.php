<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Cache\DeviceCache;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final class CacheWarmCommand extends Command
{
    protected $signature = 'devices:cache:warm';
    protected $description = 'Warm up device tracker caches';

    public function handle(): void
    {
        $this->info('Warming up device cache...');
        Device::all()->each(fn($device) => DeviceCache::put($device));

        $this->info('Warming up session cache...');
        Session::all()->each(fn($session) => SessionCache::put($session));

        $this->info('Cache warmup completed.');
    }
}
