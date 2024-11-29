<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class LivewireRequestDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 90;

    public function supports(Request $request, $response): bool
    {
        return $request->hasHeader('X-Livewire') ||
            str_starts_with($request->path(), 'livewire');
    }

    public function detect(Request $request, $response): EventType
    {
        return EventType::LivewireUpdate;
    }
}
