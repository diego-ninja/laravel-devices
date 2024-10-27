<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Contracts;

use Illuminate\Http\Response;

interface Injector
{
    public function inject(Response $response): Response;
}
