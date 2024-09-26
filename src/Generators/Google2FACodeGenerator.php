<?php

namespace Ninja\DeviceTracker\Generators;

use Ninja\DeviceTracker\Contracts\CodeGenerator;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

final readonly class Google2FACodeGenerator implements CodeGenerator
{

    public function __construct(private Google2FA $google2FA)
    {
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function generate(): string
    {
        return  $this->google2FA->generateSecretKey();
    }
}
