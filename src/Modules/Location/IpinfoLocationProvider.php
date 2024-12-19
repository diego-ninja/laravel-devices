<?php

namespace Ninja\DeviceTracker\Modules\Location;

use Exception;
use Ninja\DeviceTracker\Cache\LocationCache;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Modules\Location\Exception\LocationLookupFailedException;

final class IpinfoLocationProvider extends AbstractLocationProvider implements LocationProvider
{
    private const API_URL = 'https://ipinfo.io/%s/json';

    public function locate(string $ip): Location
    {
        $key = sprintf('%s:%s', LocationCache::KEY_PREFIX, $ip);

        $this->location = LocationCache::remember($key, function () use ($ip) {
            try {
                $url = sprintf(self::API_URL, $ip);
                $data = file_get_contents($url);

                if ($data === false) {
                    throw new Exception('Failed to fetch location data');
                }

                $locationData = json_decode($data, true);

                [$lat, $long] = explode(',', $locationData['loc']);

                $locationData['latitude'] = $lat;
                $locationData['longitude'] = $long;

                return Location::fromArray($locationData);
            } catch (Exception $e) {
                throw LocationLookupFailedException::forIp($ip, $e);
            }
        });

        return $this->location;
    }
}
