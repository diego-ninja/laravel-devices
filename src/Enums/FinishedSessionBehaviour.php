<?php

namespace Ninja\DeviceTracker\Enums;

enum FinishedSessionBehaviour: string
{
    case StartNew = 'start_new';
    case Logout = 'logout';
}
