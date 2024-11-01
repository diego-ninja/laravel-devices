<?php

namespace Ninja\DeviceTracker\Enums;

enum EventType: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Signup = 'signup';
    case PageView = 'page_view';
    case SecurityWarning = 'security_warning';
}
