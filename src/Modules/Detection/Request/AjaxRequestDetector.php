<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class AjaxRequestDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 70;

    public function supports(Request $request, $response): bool
    {
        return $request->ajax() ||
            $request->hasHeader('X-Requested-With') &&
            ! $request->hasHeader('X-Livewire');
    }

    public function detect(Request $request, $response): ?EventType
    {
        return $request->isMethod('POST') ? EventType::Submit : EventType::Click;
    }
}
