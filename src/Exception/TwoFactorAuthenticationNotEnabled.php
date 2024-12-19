<?php

namespace Ninja\DeviceTracker\Exception;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;

final class TwoFactorAuthenticationNotEnabled extends Exception
{
    public static function forUser(Authenticatable $user): self
    {
        return new self(sprintf('Two factor authentication is not enabled for user %s', $user->getAuthIdentifier()));
    }
}
