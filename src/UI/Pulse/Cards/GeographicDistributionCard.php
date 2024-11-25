<?php

namespace Ninja\DeviceTracker\UI\Pulse\Cards;

use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Livewire\Card;
use Ninja\DeviceTracker\Models\Session;

class GeographicDistributionCard extends Card
{
    public ?string $color = '#0ea5e9';

    public function render(): array
    {
        $locations = Session::select([
            'location->country as country',
            'location->city as city',
            DB::raw('COUNT(*) as sessions'),
            DB::raw('COUNT(DISTINCT device_uuid) as devices'),
            DB::raw('COUNT(DISTINCT user_id) as users'),
        ])
            ->whereNotNull('location')
            ->groupBy('location->country', 'location->city')
            ->orderByDesc('sessions')
            ->get()
            ->groupBy('country');

        $topCountries = $locations->map(fn ($cities) => [
            'sessions' => $cities->sum('sessions'),
            'devices' => $cities->sum('devices'),
            'users' => $cities->sum('users'),
            'cities' => $cities->count(),
        ])->sortByDesc('sessions');

        return [
            'locations' => $locations,
            'topCountries' => $topCountries->take(5),
            'total' => [
                'countries' => $locations->count(),
                'cities' => $locations->sum(fn ($cities) => $cities->count()),
                'sessions' => $locations->sum(fn ($cities) => $cities->sum('sessions')),
                'users' => $locations->sum(fn ($cities) => $cities->sum('users')),
            ],
        ];
    }

    public function refreshIntervalInSeconds(): int
    {
        return 60;
    }
}
