<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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
            if ($session->locked()) {
                return $this->manageLock($request);
            }

            if ($session->blocked()) {
                return $this->manageLogout($request);
            }

            if ($session->finished()) {
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
            if (Auth::guard(Config::get('devices.auth_guard'))->check()) {
                Auth::guard(Config::get('devices.auth_guard'))->logout();
            }

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
            return response()->json(['message' => 'Session locked'], 423);
        }

        try {
            return redirect()->route(Config::get('devices.2fa_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.2fa_route_name'), 'exception' => $e]);
        }

        return response()->json(['message' => 'Session locked'], 423);
    }
}
