<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final readonly class SessionTracker
{
    public function __construct(protected Guard $auth) {}

    public function handle(Request $request, Closure $next)
    {
        $device = device();
        if (! $device) {
            return $next($request);
        }

        $session = $device->sessions()->current() ?? $device->sessions()->recent();

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

            if ($session->inactive()) {
                return $this->manageInactivity($request, $next);
            }

            $session->restart($request);

            return SessionTransport::set($next($request), $session->uuid);
        }

        return $next($request);
    }

    private function manageLogout(Request $request): JsonResponse|RedirectResponse
    {
        $guard = Config::get('devices.auth_guard');
        if ($request->ajax() || ! Config::get('devices.use_redirects')) {
            if (Auth::guard($guard)->check()) {
                Auth::guard($guard)->logout();

                event(new Logout($guard, Auth::user()));
            }

            SessionTransport::forget();

            return response()->json(['message' => 'Unauthorized'], config('devices.logout_http_code', 403));
        }

        try {
            return redirect()->route(Config::get('devices.logout_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.logout_route_name'), 'exception' => $e]);
        }

        return response()->json(['message' => 'Unauthorized'], config('devices.logout_http_code', 403));
    }

    private function manageInactivity(Request $request, Closure $next): JsonResponse|RedirectResponse|Response
    {
        if (Config::get('devices.inactivity_session_behaviour') === 'terminate') {
            return $this->manageLogout($request);
        }

        return $next($request);
    }

    private function manageLock(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || ! Config::get('devices.use_redirects')) {
            return response()->json(['message' => 'Session locked'], config('devices.lock_http_code', 403));
        }

        try {
            return redirect()->route(Config::get('devices.2fa_route_name'));
        } catch (RouteNotFoundException $e) {
            Log::error('Route not found', ['route' => Config::get('devices.2fa_route_name'), 'exception' => $e]);
        }

        return response()->json(['message' => 'Session locked'], config('devices.lock_http_code', 403));
    }
}
