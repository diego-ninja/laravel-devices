<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Http\Resources\SessionResource;

final class SessionController extends Controller
{
    /**
     * @authenticated
     */
    public function list(Request $request): JsonResponse
    {
        $sessions = $this->getUserSessions($request);
        return response()->json(SessionResource::collection($sessions)->with($request));
    }

    /**
     * @authenticated
     */
    public function show(Request $request): JsonResponse
    {
        $session = $this->findUserSession($request, $request->input('id'));

        if ($session) {
            return response()->json(SessionResource::make($session)->with($request));
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    /**
     * End the session by given id
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function end(Request $request): JsonResponse
    {
        $session = $this->findUserSession($request, $request->input('id'));

        if ($session) {
            $session->end();
            return response()->json(['message' => 'Session ended successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function lock(Request $request): JsonResponse
    {
        $session = $this->findUserSession($request, $request->input('id'));

        if ($session) {
            $session->lock();
            return response()->json([
                'message' => 'Session locked successfully with code: ' . $session->login_code,
                'login_code' => $session->login_code
            ]);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function unlock(Request $request): JsonResponse
    {
        $session = $this->findUserSession($request, $request->input('id'));
        $code = $request->input('login_code');

        if ($session) {
            if ($session->unlockByCode($code)) {
                return response()->json(['message' => 'Session unlocked successfully']);
            } else {
                return response()->json(['message' => 'Invalid code'], 401);
            }
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    private function getUserSessions(Request $request)
    {
        return $request
            ->user(Config::get('devices.auth_guard'))
            ->sessions()
            ->with("device")
            ->get();
    }

    private function findUserSession(Request $request, $id)
    {
        return $request
            ->user(Config::get('devices.auth_guard'))
            ->sessions()
            ->with("device")
            ->find($id);
    }
}
