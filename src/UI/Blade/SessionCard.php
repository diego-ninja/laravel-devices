<?php

namespace Ninja\DeviceTracker\UI\Blade;

use Illuminate\View\Component;
use Ninja\DeviceTracker\Models\Session;

class SessionCard extends Component
{
    public function __construct(
        public Session $session,
        public bool $showActions = true
    ) {}

    public function isCurrentSession(): bool
    {
        return $this->session->uuid === session_uuid();
    }

    public function deviceIcon(): string
    {
        return $this->session->device->device_type === 'mobile'
            ? 'heroicon-o-device-phone-mobile'
            : 'heroicon-o-computer-desktop';
    }

    public function statusIcon(): string
    {
        return match ($this->session->status->value) {
            'locked' => 'heroicon-s-lock-closed',
            'blocked' => 'heroicon-s-x-circle',
            default => 'heroicon-s-shield-check'
        };
    }

    public function statusClasses(): string
    {
        return match ($this->session->status->value) {
            'active' => 'border-green-500 bg-white',
            'locked' => 'border-yellow-500 bg-yellow-50',
            'blocked' => 'border-red-500 bg-red-50',
            default => 'border-gray-500 bg-white'
        };
    }

    public function badgeClasses(): string
    {
        return match ($this->session->status->value) {
            'active' => 'bg-green-100 text-green-800',
            'locked' => 'bg-yellow-100 text-yellow-800',
            'blocked' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    public function render(): mixed
    {
        return view('laravel-devices::blade.session-card');
    }
}
