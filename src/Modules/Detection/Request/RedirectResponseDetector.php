<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class RedirectResponseDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 60;

    public function supports(Request $request, mixed $response): bool
    {
        return $response instanceof RedirectResponse;
    }

    public function detect(Request $request, mixed $response): EventType
    {
        return EventType::Redirect;
    }
}
