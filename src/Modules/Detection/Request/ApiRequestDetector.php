<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class ApiRequestDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 80;

    public function supports(Request $request, mixed $response): bool
    {
        return $request->is('api/*') ||
            $this->json($request) ||
            $request->expectsJson();
    }

    public function detect(Request $request, mixed $response): EventType
    {
        return EventType::ApiRequest;
    }
}
