<?php

namespace Ninja\DeviceTracker\Modules\Detection\Contracts;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

interface RequestTypeDetector
{
    public function supports(Request $request, mixed $response): bool;

    public function detect(Request $request, mixed $response): ?EventType;

    public function priority(): int;
}
