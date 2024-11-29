<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Models\Session;
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
                return $this->manageLogout($request, $session);
            }

            if ($session->finished()) {
                return $this->manageLogout($request, $session);
            }

            if ($session->inactive()) {
                return $this->manageInactivity($request, $session, $next);
            }

            $session->restart($request);

            return SessionTransport::set($next($request), $session->uuid);
        }

        return $next($request);
    }

    private function manageLogout(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        /** @var StatefulGuard $guard */
        $guard = Auth::guard(Config::get('devices.auth_guard'));
        $user = $guard->user();

        if (! $user) {
            $session->end();
        } else {
            $guard->logout();
            $session->end(user: $user);

            event(new Logout(Config::get('devices.auth_guard'), $user));
        }

        if ($request->ajax() || ! Config::get('devices.use_redirects')) {
            return response()->json(['message' => 'Unauthorized'], config('devices.logout_http_code', 403));
        } else {
            try {
                return redirect()->route(Config::get('devices.login_route_name'));
            } catch (RouteNotFoundException $e) {
                Log::error('Route not found', ['route' => Config::get('devices.login_route_name'), 'exception' => $e]);
            }
        }

        return response()->json(['message' => 'Unauthorized'], config('devices.logout_http_code', 403));
    }

    private function manageInactivity(Request $request, Session $session, Closure $next): JsonResponse|RedirectResponse|Response
    {
        if (Config::get('devices.inactivity_session_behaviour') === 'terminate') {
            return $this->manageLogout($request, $session);
        } else {
            $session->status = SessionStatus::Inactive;
            $session->save();
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
