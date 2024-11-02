<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Modules\Detection\Contracts\RequestTypeDetector;

abstract class AbstractRequestDetector implements RequestTypeDetector
{
    protected const PRIORITY = 0;

    public function priority(): int
    {
        return static::PRIORITY;
    }

    protected function json(Request $request): bool
    {
        return
            ($request->hasHeader('Accept') && str_contains($request->header('Accept'), 'application/json')) ||
            ($request->hasHeader('Content-Type') && str_contains($request->header('Content-Type'), 'application/json'));
    }

    protected function html(mixed $response): bool
    {
        return
            $response instanceof Response &&
            str_contains($response->headers->get('Content-Type', ''), 'text/html');
    }
}
