<?php

namespace Ninja\DeviceTracker\Enums;

enum SessionIpChangeBehaviour: string
{
    case Relocate = 'relocate';
    case StartNew = 'start_new';
    case None = 'none';
}
