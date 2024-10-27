<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Exception\TwoFactorAuthenticationNotEnabled;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Http\Resources\SessionResource;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\ValueObject\SessionId;
use PragmaRX\Google2FA\Exceptions\InvalidAlgorithmException;
use Ramsey\Uuid\Uuid;
use Random\RandomException;

/**
 * @authenticated
 */
final class SessionController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $sessions = $this->getUserSessions($request);
        return response()->json(SessionResource::collection($sessions));
    }

    public function active(Request $request): JsonResponse
    {
        $sessions = $this->getUserActiveSessions($request);
        return response()->json(SessionResource::collection($sessions));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            return response()->json(SessionResource::make($session));
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function end(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $session->end();
            return response()->json(['message' => 'Session ended successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function block(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $session->block();
            return response()->json(['message' => 'Session blocked successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function unblock(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $session->unblock();
            return response()->json(['message' => 'Session unblocked successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function renew(Request $request, string $id): JsonResponse
    {
        $user = $request->user(Config::get('devices.auth_guard'));
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $session->renew($user);
            return response()->json(['message' => 'Session renewed successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function signout(Request $request): JsonResponse
    {
        $user = $request->user(Config::get('devices.auth_guard'));
        $user->signout(true);

        return response()->json(['message' => 'Signout successful']);
    }

    private function getUserSessions(Request $request)
    {
        $user = $request->user(Config::get('devices.auth_guard'));
        return SessionCache::userSessions($user);
    }

    private function getUserActiveSessions(Request $request)
    {
        $user = $request->user(Config::get('devices.auth_guard'));
        return SessionCache::activeSessions($user);
    }

    private function findUserSession(Request $request, string $id): ?Session
    {
        $user = $request->user(Config::get('devices.auth_guard'));

        $sessions = SessionCache::userSessions($user);
        return $sessions->where('uuid', SessionIdFactory::from($id))->first();
    }
}
