<?php

namespace Ninja\DeviceTracker\Modules\Location;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Modules\Location\Exception\LocationLookupFailedException;

final class FallbackLocationProvider extends AbstractLocationProvider
{
    /**
     * @var Collection<int, LocationProvider>
     */
    private Collection $providers;

    public function __construct()
    {
        $this->providers = new Collection([]);
    }

    /**
     * @throws LocationLookupFailedException
     */
    public function locate(string $ip): Location
    {
        foreach ($this->providers as $provider) {
            try {
                $this->location = $provider->locate($ip);

                return $this->location;
            } catch (\Exception $e) {
                Log::warning('Location provider failed', [
                    'provider' => get_class($provider),
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }
        }

        throw LocationLookupFailedException::forIp($ip, null);
    }

    public function addProvider(LocationProvider $provider): void
    {
        $this->providers->add($provider);
    }
}
