<?php

namespace Ninja\DeviceTracker\Transports;

use Ninja\DeviceTracker\Enums\Transport;
use Ninja\DeviceTracker\Factories\AbstractStorableIdFactory;
use Ninja\DeviceTracker\Factories\FingerprintFactory;

class FingerprintTransport extends AbstractTransport
{
    protected const CONFIG_PARAMETER = 'fingerprint_parameter';
    protected const CONFIG_ALTERNATIVE_PARAMETER = 'fingerprint_alternative_parameter';
    protected const CONFIG_TRANSPORT_HIERARCHY_KEY = 'fingerprint_transport_hierarchy';
    protected const CONFIG_RESPONSE_TRANSPORT_KEY = 'fingerprint_response_transport';
    protected const DEFAULT_TRANSPORT = Transport::Cookie;
    protected const DEFAULT_RESPONSE_TRANSPORT = Transport::Cookie;

    public static function make(Transport $transport): static
    {
        return new self($transport);
    }

    /**
     * @return class-string<AbstractStorableIdFactory>
     */
    protected static function storableIdFactory(): string
    {
        return FingerprintFactory::class;
    }
}
