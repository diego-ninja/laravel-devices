<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class LocationVelocityRule extends AbstractSecurityRule
{
    private const MAX_VELOCITY = 900;
    private const EARTH_RADIUS = 6371;

    public function evaluate(array $context): Factor
    {
        $session = $this->session();
        if (!$session) {
            return new Factor($this->factor, 0.0);
        }

        $lastSession = Session::where('user_id', $session->user_id)
            ->where('id', '!=', $session->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastSession) {
            return new Factor($this->factor, 0.0);
        }

        if (!$lastSession->location || !$session->location) {
            return new Factor($this->factor, 0.0);
        }

        $distance = $this->distance(
            $lastSession->location,
            $session->location
        );

        $timeDiff = $lastSession->created_at->diffInHours($session->started_at) ?: 1;
        $velocity = $distance / $timeDiff;

        $score = $velocity > self::MAX_VELOCITY ? 1.0 : 0.0;
        return new Factor($this->factor, $score);
    }

    private function distance(Location $loc1, Location $loc2): float
    {
        $lat1 = deg2rad($loc1->latitude);
        $lon1 = deg2rad($loc1->longitude);
        $lat2 = deg2rad($loc2->latitude);
        $lon2 = deg2rad($loc2->longitude);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS * $c; // Radio de la Tierra * c = distancia en km
    }
}
