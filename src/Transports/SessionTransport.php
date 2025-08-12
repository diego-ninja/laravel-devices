<?php

namespace Ninja\DeviceTracker\Transports;

use Ninja\DeviceTracker\Enums\Transport;
use Ninja\DeviceTracker\Factories\AbstractStorableIdFactory;
use Ninja\DeviceTracker\Factories\SessionIdFactory;

class SessionTransport extends AbstractTransport
{
    protected const CONFIG_PARAMETER = 'session_id_parameter';
    protected const CONFIG_ALTERNATIVE_PARAMETER = 'session_id_alternative_parameter';
    protected const CONFIG_TRANSPORT_HIERARCHY_KEY = 'session_id_transport_hierarchy';
    protected const CONFIG_RESPONSE_TRANSPORT_KEY = 'session_id_response_transport';
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
        return SessionIdFactory::class;
    }
}
