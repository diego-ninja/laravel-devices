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
        $accept = $request->header('Accept');
        $contentType = $request->header('Content-Type');

        if (! $accept || ! $contentType) {
            return false;
        }

        return
            is_string($accept) && str_contains($accept, 'application/json') ||
            is_string($contentType) && str_contains($contentType, 'application/json');
    }

    protected function html(mixed $response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        if (! $contentType) {
            return false;
        }

        return
            $response instanceof Response &&
            is_string($contentType) && str_contains($contentType, 'text/html');
    }
}
