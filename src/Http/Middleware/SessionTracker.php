<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Models\Session;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

final readonly class SessionTracker
{
    public function __construct(protected Guard $auth) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $device = device();
        if ($device === null) {
            return $next($request);
        }

        $session = device_session();

        if ($session !== null) {
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

        if (guard()->check()) {
            try {
                $session = SessionManager::start();

                return SessionTransport::set($next($request), $session->uuid);
            } catch (DeviceNotFoundException $e) {
                Log::error('Failed to start session', ['error' => $e->getMessage()]);
            }
        }

        return $next($request);
    }

    private function manageLogout(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        $user = user();
        $guard = guard();

        if ($user !== null) {
            if ($user->google2faEnabled()) {
                $user->google2fa->last_success_at = null;
                $user->google2fa->save();
            }

            $guard->logout();
            $session->end(user: $user);
            event(new Logout(config('devices.auth_guard'), $user));
        } else {
            $session->end();
        }

        if ($request->ajax() || config('devices.use_redirects') === false) {
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
        if ($request->ajax() || config('devices.use_redirects') === false) {
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
