<?php

namespace Ninja\DeviceTracker\Modules\Tracking\Enums;

enum EventType: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Signup = 'signup';
    case PageView = 'page_view';
    case Click = 'click';
    case Submit = 'submit';
    case ApiRequest = 'api_request';
    case SecurityWarning = 'security_warning';
    case Redirect = 'redirect';
    case LivewireUpdate = 'livewire_update';

    public function equals(EventType $eventType): bool
    {
        return $this->value === $eventType->value;
    }
}
