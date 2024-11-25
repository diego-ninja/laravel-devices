<?php

namespace Ninja\DeviceTracker\UI\Blade;

use Illuminate\View\Component;
use Ninja\DeviceTracker\Models\Session;

class SessionCard extends Component
{
    public function __construct(
        public Session $session
    ) {}

    public function isCurrentSession(): bool
    {
        return $this->session->uuid === session_uuid();
    }

    public function statusIcon(): string
    {
        return match ($this->session->status->value) {
            'locked' => 'lock-closed',
            'blocked' => 'ban',
            default => 'shield-check'
        };
    }

    public function render(): mixed
    {
        return view('laravel-devices::blade.session-card');
    }
}
