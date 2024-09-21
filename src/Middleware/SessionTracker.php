<?php

namespace Ninja\DeviceTracker\Middleware;

use Auth;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
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
            if ($session->isLocked()) {
                return $this->manageLock($request);
            }

            if ($session->isBlocked()) {
                return $this->manageLogout($request);
            }

            if ($session->isInactive()) {
                return $this->manageLogout($request);
            }

            $session->restart($request);
        }

        return $next($request);
    }

    private function manageLogout(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || !Config::get('devices.use_redirects')) {
            Auth::guard(Config::get('devices.logout_guard'))->logout();
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            return redirect()->route(Config::get('devices.logout_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.logout_route_name'), 'exception' => $e]);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }

    private function manageLock(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || !Config::get('devices.use_redirects')) {
            return response()->json(['message' => 'Session locked'], 401);
        }

        try {
            return redirect()->route(Config::get('devices.security_code_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.logout_route_name'), 'exception' => $e]);
        }

        return response()->json(['message' => 'Session locked'], 401);
    }
}
