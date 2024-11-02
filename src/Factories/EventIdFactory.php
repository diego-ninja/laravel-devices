<?php

namespace Ninja\DeviceTracker\Factories;

class EventIdFactory extends AbstractStorableIdFactory
{
    protected function getIdClass(): string
    {
        return config('devices.event_id_storable_class');
    }
}