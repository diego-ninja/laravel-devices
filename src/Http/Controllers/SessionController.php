<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Http\Resources\SessionResource;
use Ninja\DeviceTracker\Models\Session;

/**
 * @authenticated
 */
final class SessionController extends Controller
{
    public function list(): JsonResponse
    {
        $sessions = $this->getUserSessions();

        return response()->json(SessionResource::collection($sessions));
    }

    public function active(): JsonResponse
    {
        $sessions = $this->getUserActiveSessions();

        return response()->json(SessionResource::collection($sessions));
    }

    public function show(string $id): JsonResponse
    {
        $session = $this->findUserSession($id);

        if ($session !== null) {
            return response()->json(SessionResource::make($session));
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function end(string $id): JsonResponse
    {
        $session = $this->findUserSession($id);

        if ($session !== null) {
            $session->end();

            return response()->json(['message' => 'Session ended successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function block(string $id): JsonResponse
    {
        $session = $this->findUserSession($id);

        if ($session !== null) {
            $session->block();

            return response()->json(['message' => 'Session blocked successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function unblock(string $id): JsonResponse
    {
        $session = $this->findUserSession($id);

        if ($session !== null) {
            $session->unblock();

            return response()->json(['message' => 'Session unblocked successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function renew(string $id): JsonResponse
    {
        $user = user();
        $session = $this->findUserSession($id);

        if ($session !== null) {
            $session->renew($user);

            return response()->json(['message' => 'Session renewed successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function signout(): JsonResponse
    {
        $user = user();
        $user?->signout(true);

        return response()->json(['message' => 'Signout successful']);
    }

    /**
     * @return Collection<int, Session>|null
     */
    private function getUserSessions(): ?Collection
    {
        $user = user();
        if ($user === null) {
            return null;
        }

        return SessionCache::userSessions($user);
    }

    /**
     * @return Collection<int, Session>|null
     */
    private function getUserActiveSessions(): ?Collection
    {
        $user = user();
        if ($user === null) {
            return null;
        }

        return SessionCache::activeSessions($user);
    }

    private function findUserSession(string $id): ?Session
    {
        $user = user();
        if ($user === null) {
            return null;
        }

        $sessions = SessionCache::userSessions($user);

        return $sessions?->where('uuid', SessionIdFactory::from($id))->first();
    }
}
