<?php

namespace Ninja\DeviceTracker\Factories;

use Ninja\DeviceTracker\Contracts\StorableId;

abstract class AbstractStorableIdFactory
{
    protected static ?self $instance = null;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public static function generate(): StorableId
    {
        $idClass = self::instance()->getIdClass();
        return $idClass::generate();
    }

    public static function from(string $id): StorableId
    {
        $idClass = self::instance()->getIdClass();
        return $idClass::fromString($id);
    }

    abstract protected function getIdClass(): string;
}
