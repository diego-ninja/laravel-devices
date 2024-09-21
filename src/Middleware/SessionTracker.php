<?php

namespace Ninja\DeviceTracker\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Models\Session;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final readonly class SessionTracker
{
    public function __construct(protected Guard $auth)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $session = Session::current();
        if ($session) {
            if ($session->isBlocked()) {
                return $this->logout($request);
            }

            if ($session->isInactive()) {
                return $this->logout($request);
            }

            if ($session->isLocked()) {
                return $this->manageLock($request);
            }

            $session->restart($request);
        }

        return $next($request);
    }

    private function logout(Request $request): Response|RedirectResponse
    {
        if ($request->ajax() || !Config::get('devices.use_redirects')) {
            return response('Unauthorized.', 401);
        }

        try {
            return redirect()->route(Config::get('devices.logout_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.logout_route_name'), 'exception' => $e]);
        }

        return response('Unauthorized.', 401);
    }

    private function manageLock(Request $request): Response|RedirectResponse
    {
        if ($request->ajax() || !Config::get('devices.use_redirects')) {
            return response('Session locked.', 401);
        }

        try {
            return redirect()->route(Config::get('devices.security_code_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.logout_route_name'), 'exception' => $e]);
        }

        return response('Session locked.', 401);
    }
}
