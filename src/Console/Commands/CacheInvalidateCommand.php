<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Cache\DeviceCache;
use Ninja\DeviceTracker\Cache\LocationCache;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Cache\UserAgentCache;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final class CacheInvalidateCommand extends Command
{
    protected $signature = 'devices:cache:invalidate';
    protected $description = 'Invalidate device tracker caches';

    public function handle(): void
    {
        // Invalidate all caches
        $this->info('Invalidating device cache...');
        Device::all()->each(fn($device) => DeviceCache::forget($device));

        $this->info('Invalidating session cache...');
        Session::all()->each(fn($session) => SessionCache::forget($session));

        $this->info('Invalidating location cache...');
        LocationCache::flush();

        $this->info('Invalidating user agent cache...');
        UserAgentCache::flush();

        $this->info('Cache invalidation completed.');
    }
}
