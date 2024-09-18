<?php

namespace Ninja\DeviceTracker\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Facades\DeviceTrackerFacade;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;

final readonly class SessionTracker
{
    public function __construct(protected Guard $auth)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($this->auth->guest()) {
            return $this->handleGuest($request);
        }

        if ($this->isSessionBlockedOrInactive()) {
            return $this->handleBlockedOrInactiveSession($request);
        }

        if ($this->isSessionLocked()) {
            return $this->redirectToSecurityCode();
        }

        $this->refreshAndLogSession($request);

        return $next($request);
    }

    private function handleGuest(Request $request): Response|RedirectResponse
    {
        if ($request->ajax()) {
            return response('Unauthorized.', 401);
        }

        return redirect()->route(Config::get('devices.logout_route_name'));
    }

    private function isSessionBlockedOrInactive(): bool
    {
        return DeviceTrackerFacade::isSessionBlocked() || DeviceTrackerFacade::isSessionInactive();
    }

    private function handleBlockedOrInactiveSession(Request $request): Response|RedirectResponse
    {
        if ($request->ajax()) {
            return response('Unauthorized.', 401);
        }

        return redirect()->route(Config::get('devices.logout_route_name'));
    }

    private function isSessionLocked(): bool
    {
        return DeviceTrackerFacade::isSessionLocked();
    }

    private function redirectToSecurityCode(): RedirectResponse
    {
        return redirect()->route(Config::get('devices.security_code_route_name'));
    }

    private function refreshAndLogSession(Request $request): void
    {
        DeviceTrackerFacade::refreshSession($request);
        DeviceTrackerFacade::logSession($request);
    }
}
