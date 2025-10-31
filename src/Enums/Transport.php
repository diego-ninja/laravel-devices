<?php

namespace Ninja\DeviceTracker\Enums;

enum Transport: string
{
    case Cookie = 'cookie';
    case Header = 'header';
    case Session = 'session';
    case Request = 'request';
}
