<?php

namespace Ninja\DeviceTracker\Contracts;

use Illuminate\Http\Request;

interface RequestAware
{
    public function setRequest(Request $request): void;

    public function request(): Request;
}
