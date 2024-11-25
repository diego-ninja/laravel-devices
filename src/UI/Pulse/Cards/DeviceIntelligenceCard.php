<?php

namespace Ninja\DeviceTracker\UI\Pulse\Cards;

use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Livewire\Card;
use Ninja\DeviceTracker\Models\Device;

class DeviceIntelligenceCard extends Card
{
    public ?string $color = '#8b5cf6';

    public function render(): array
    {
        $devices = Device::select([
            'browser_family',
            'platform_family',
            'device_type',
            DB::raw('COUNT(*) as count'),
            DB::raw('COUNT(DISTINCT user_id) as users'),
            DB::raw('AVG(risk_score) as avg_risk'),
        ])
            ->with(['sessions' => function ($query) {
                $query->where('last_activity_at', '>=', now()->subDay());
            }])
            ->groupBy('browser_family', 'platform_family', 'device_type')
            ->get();

        $outdatedBrowsers = Device::whereRaw("CAST(SUBSTRING_INDEX(browser_version, '.', 1) AS UNSIGNED) < ?", [100])
            ->count();

        return [
            'distribution' => [
                'browsers' => $devices->groupBy('browser_family')
                    ->map(fn ($group) => [
                        'count' => $group->sum('count'),
                        'users' => $group->sum('users'),
                        'risk' => round($group->avg('avg_risk'), 2),
                    ]),
                'platforms' => $devices->groupBy('platform_family')
                    ->map(fn ($group) => [
                        'count' => $group->sum('count'),
                        'users' => $group->sum('users'),
                        'risk' => round($group->avg('avg_risk'), 2),
                    ]),
                'types' => $devices->groupBy('device_type')
                    ->map(fn ($group) => [
                        'count' => $group->sum('count'),
                        'users' => $group->sum('users'),
                        'risk' => round($group->avg('avg_risk'), 2),
                    ]),
            ],
            'stats' => [
                'total_devices' => $devices->sum('count'),
                'active_today' => $devices->sum(fn ($d) => $d->sessions->count()),
                'outdated_browsers' => $outdatedBrowsers,
                'avg_risk' => round($devices->avg('avg_risk'), 2),
            ],
        ];
    }

    public function refreshIntervalInSeconds(): int
    {
        return 60;
    }
}
