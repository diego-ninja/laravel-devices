<?php

namespace Ninja\DeviceTracker\UI\Livewire;

use Livewire\Component;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Models\Session;

class SessionMap extends Component
{
    public ?StorableId $selectedSession = null;

    public string $mapboxToken;

    public $mapboxStyle = 'mapbox://styles/mapbox/light-v11'; // Monochrome style

    public array $mapBounds = [];

    public function mount(): void
    {
        $this->mapboxToken = config('services.mapbox.public_token');
        $this->calculateMapBounds();
    }

    public function getListeners(): array
    {
        return [
            'session-status-changed' => 'calculateMapBounds',
            'session-ended' => 'handleSessionEnded',
            'session-blocked' => 'handleSessionBlocked',
            'session-unblocked' => 'handleSessionUnblocked',
        ];
    }

    public function selectSession($sessionId): void
    {
        $this->selectedSession = $sessionId === $this->selectedSession ? null : $sessionId;
    }

    /**
     * @throws SessionNotFoundException
     */
    public function endSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->end(forgetSession: true);
            $this->dispatch('session-ended', sessionId: $sessionId);
            $this->dispatch('notify', [
                'message' => __('Session ended successfully'),
                'type' => 'success',
            ]);
            $this->selectedSession = null;
        }
    }

    /**
     * @throws SessionNotFoundException
     */
    public function blockSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->block();
            $this->dispatch('session-blocked', sessionId: $sessionId);
            $this->dispatch('notify', [
                'message' => __('Session blocked successfully'),
                'type' => 'success',
            ]);
        }
    }

    /**
     * @throws SessionNotFoundException
     */
    public function unblockSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->unblock();
            $this->dispatch('session-unblocked', sessionId: $sessionId);
            $this->dispatch('notify', [
                'message' => __('Session unblocked successfully'),
                'type' => 'success',
            ]);
        }
    }

    private function calculateMapBounds(): void
    {
        $sessions = SessionCache::userSessions(auth()->user());

        if ($sessions->isEmpty()) {
            // Default to world view
            $this->mapBounds = [
                'north' => 85,
                'south' => -85,
                'east' => 180,
                'west' => -180,
            ];

            return;
        }

        $locations = $sessions->map(fn ($session) => [
            'lat' => (float) $session->location->latitude,
            'lng' => (float) $session->location->longitude,
        ]);

        $this->mapBounds = [
            'north' => $locations->max('lat') + 5,
            'south' => $locations->min('lat') - 5,
            'east' => $locations->max('lng') + 5,
            'west' => $locations->min('lng') - 5,
        ];
    }

    public function render(): mixed
    {
        return view('laravel-devices::livewire.session-map', [
            'sessions' => SessionCache::userSessions(auth()->user()),
        ]);
    }
}
