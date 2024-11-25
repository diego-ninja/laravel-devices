<?php

namespace Ninja\DeviceTracker\UI\Blade;

use Illuminate\View\Component;
use Ninja\DeviceTracker\Models\Session;

class SessionTooltip extends Component
{
    public function __construct(
        public ?Session $session = null
    ) {}

    public function deviceIcon(): string
    {
        return $this->session->device->device_type === 'mobile'
            ? 'heroicon-o-device-phone-mobile'
            : 'heroicon-o-computer-desktop';
    }

    public function statusClasses(): string
    {
        return match ($this->session->status->value) {
            'active' => 'bg-green-100 text-green-800 border-green-500',
            'locked' => 'bg-yellow-100 text-yellow-800 border-yellow-500',
            'blocked' => 'bg-red-100 text-red-800 border-red-500',
            default => 'bg-gray-100 text-gray-800 border-gray-500'
        };
    }

    public function render(): mixed
    {
        return view('devices::blade.session-tooltip');
    }
}
