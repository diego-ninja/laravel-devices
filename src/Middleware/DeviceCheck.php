<?php

namespace Ninja\DeviceTracker\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Config;
use Ramsey\Uuid\Uuid;

final readonly class DeviceCheck
{
    public function handle(Request $request, Closure $next)
    {

        if (!Cookie::has('d_i')) {
            Cookie::queue(
                Cookie::forever(
                    name: 'd_i',
                    value: Uuid::uuid7()->toString(),
                    secure: Config::get('session.secure', false),
                    httpOnly: Config::get('session.http_only', true)
                )
            );
        }

        return $next($request);
    }
}
