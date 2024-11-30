<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Contracts;

use Illuminate\Http\Response;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums\Library;

interface Injector
{
    public function inject(Response $response): Response;

    public function library(): Library;
}
