<?php

use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Observability\Enums\MetricName;
use Ninja\DeviceTracker\Modules\Observability\MetricAggregator;

if (! function_exists('fingerprint')) {
    function fingerprint(): ?string
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            $cookie = Config::get('devices.fingerprint_id_cookie_name');
            return Cookie::has($cookie) ? Cookie::get($cookie) : null;
        }

        return null;
    }
}

if (! function_exists('device_uuid')) {
    function device_uuid(): ?StorableId
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        if (Cookie::has($cookieName)) {
            return DeviceIdFactory::from(Cookie::get($cookieName));
        }

        $requestParam = Config::get('devices.device_id_request_param');
        if (request()->has($requestParam)) {
            return DeviceIdFactory::from(request()->$requestParam);
        }

        return null;
    }
}

if (! function_exists('session_uuid')) {
    function session_uuid(): ?StorableId
    {
        $id = SessionFacade::get(Session::DEVICE_SESSION_ID);
        return $id ? SessionIdFactory::from($id) : null;
    }
}

if (! function_exists('session')) {
    function session(): ?Session
    {
        try {
            $id = session_uuid();
            return $id ? Session::byUuid($id) : null;
        } catch (SessionNotFoundException) {
            return null;
        }
    }
}

if (! function_exists('device')) {
    function device(bool $cached = true): ?Device
    {

        if (Config::get('devices.fingerprinting_enabled')) {
            $fingerprint = fingerprint();
            if ($fingerprint) {
                return Device::byFingerprint($fingerprint, $cached);
            }
        }

        $id = device_uuid();
        return $id ? Device::byUuid($id, $cached) : null;
    }
}

if (! function_exists('counter')) {
    function counter(string $name, float $value = 1, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->counter(MetricName::from($name), $value, $dimensions);
    }
}

if (! function_exists('gauge')) {
    function gauge(string $name, float $value, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->gauge(MetricName::from($name), $value, $dimensions);
    }
}

if (! function_exists('histogram')) {
    function histogram(string $name, float $value, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->histogram(MetricName::from($name), $value, $dimensions);
    }
}

if (! function_exists('average')) {
    function average(string $name, float $value, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->average(MetricName::from($name), $value, $dimensions);
    }
}

if (! function_exists('rate')) {
    function rate(string $name, float $value = 1, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->rate(MetricName::from($name), $value, $dimensions);
    }
}

if (! function_exists('summary')) {
    function summary(string $name, float $value, array $dimensions = []): void
    {
        $aggregator = app(MetricAggregator::class);
        $aggregator->summary(MetricName::from($name), $value, $dimensions);
    }
}
