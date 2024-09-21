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
            }

            if ($session->isInactive()) {
            }

            if ($session->isLocked()) {
            }

            $session->restart($request);
        } else {
        }



        if ($request->session()->has(Session::DEVICE_SESSION_ID)) {
            SessionManager::restart($request);
        } else {
            SessionManager::end(forgetSession: true);
        }

        return $next($request);
    }

    private function isSessionBlockedOrInactive(): bool
    {
        return SessionManager::isBlocked() || SessionManager::isInactive();
    }

    private function handleBlockedOrInactiveSession(Request $request): Response|RedirectResponse
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

    private function isSessionLocked(): bool
    {
        return SessionManager::isLocked();
    }

    private function redirectToSecurityCode(): RedirectResponse
    {
        return redirect()->route(Config::get('devices.security_code_route_name'));
    }

    private function restartAndLogSession(Request $request): void
    {
        SessionManager::restart($request);
    }
}
