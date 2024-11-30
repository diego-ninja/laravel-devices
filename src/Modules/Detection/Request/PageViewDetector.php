<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class PageViewDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 50;

    public function supports(Request $request, mixed $response): bool
    {
        return $request->isMethod('GET') &&
            ! $request->ajax() &&
            $this->html($response);
    }

    public function detect(Request $request, mixed $response): EventType
    {
        return EventType::PageView;
    }
}
