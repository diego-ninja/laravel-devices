<?php

namespace Ninja\DeviceTracker\Modules\Security\Exceptions;

use Exception;
use Ninja\DeviceTracker\Contracts\StorableId;

final class InvalidSecurityFactorScoreException extends Exception
{
    public static function make(string $factorName, float $score): self
    {
        return new self(sprintf(
            'Invalid factor score for factor %s: %s. Value between 0 and 1 expected',
            $factorName,
            $score,
        ));
    }
}
