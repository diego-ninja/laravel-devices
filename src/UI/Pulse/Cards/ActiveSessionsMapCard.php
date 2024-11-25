<?php

namespace Ninja\DeviceTracker\UI\Pulse\Cards;

use Laravel\Pulse\Livewire\Card;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

class ActiveSessionsMapCard extends Card
{
    public ?string $color = '#16a34a';

    public function render(): array
    {
        $sessions = Session::with(['device', 'user'])
            ->whereIn('status', [
                SessionStatus::Active,
                SessionStatus::Inactive,
                SessionStatus::Blocked,
                SessionStatus::Locked,
            ])
            ->whereNotNull('location')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->uuid,
                'lat' => $session->location->latitude,
                'lng' => $session->location->longitude,
                'status' => $session->status,
                'inactive' => $session->inactive(),
                'device' => [
                    'uuid' => $session->device->uuid,
                    'name' => $session->device->device_family.' '.$session->device->device_model,
                    'browser' => $session->device->browser.' '.$session->device->browser_version,
                    'platform' => $session->device->platform.' '.$session->device->platform_version,
                    'hijacked' => $session->device->hijacked(),
                ],
                'user' => [
                    'id' => $session->user->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                ],
                'session' => [
                    'ip' => $session->ip,
                    'location' => (string) $session->location,
                    'last_activity' => $session->last_activity_at->diffForHumans(),
                ],
            ]);

        return [
            'sessions' => $sessions,
            'stats' => [
                'total' => $sessions->count(),
                'active' => $sessions->filter(fn ($s) => $s['status'] === SessionStatus::Active && ! $s['inactive'])->count(),
                'inactive' => $sessions->filter(fn ($s) => $s['inactive'])->count(),
                'blocked' => $sessions->filter(fn ($s) => $s['status'] === SessionStatus::Blocked || $s['status'] === SessionStatus::Locked)->count(),
            ],
        ];
    }

    public function refreshIntervalInSeconds(): int
    {
        return 5;
    }
}
