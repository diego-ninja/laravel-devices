<?php

namespace Ninja\DeviceTracker\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SessionActivityMap extends Component
{
    public $sessions;

    public $timeRange = '24h';

    public function mount(): void
    {
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $query = Auth::user()->sessions()
            ->with(['device', 'events'])
            ->whereNotNull('location');

        $query->when($this->timeRange === '24h', function ($q) {
            $q->where('last_activity_at', '>=', now()->subHours(24));
        })->when($this->timeRange === '7d', function ($q) {
            $q->where('last_activity_at', '>=', now()->subDays(7));
        })->when($this->timeRange === '30d', function ($q) {
            $q->where('last_activity_at', '>=', now()->subDays(30));
        });

        $this->sessions = $query->get();
    }

    public function setTimeRange($range): void
    {
        $this->timeRange = $range;
        $this->loadSessions();
    }

    public function render(): mixed
    {
        return view('livewire.session-activity-map');
    }
}
