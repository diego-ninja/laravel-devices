<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;

final class TwoFactorAuthenticationNotEnabled extends Exception
{
    public function __construct()
    {
        parent::__construct('Two-factor authentication is not enabled for this user.');
    }
}