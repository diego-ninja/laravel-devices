<?php

namespace Ninja\DeviceTracker\Enums;

enum EventType: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Singup = 'register';
    case PageView = 'page_view';
}