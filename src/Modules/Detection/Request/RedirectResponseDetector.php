<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class RedirectResponseDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 60;

    public function supports(Request $request, $response): bool
    {
        return $response instanceof \Illuminate\Http\RedirectResponse;
    }

    public function detect(Request $request, $response): ?EventType
    {
        return EventType::Redirect;
    }
}
