<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\DTO\Session as SessionDTO;

final class SessionController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $ret = [];

        $sessions = Auth::user()->sessions;
        foreach ($sessions as $session) {
            $ret[] = SessionDTO::fromModel($session);
        }
        return response()->json($ret);
    }

    public function show(Request $request): JsonResponse
    {
        $session = Auth::user()->sessions()->find($request->input('id'));

        if ($session) {
            return response()->json(SessionDTO::fromModel($session));
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
        $session = Session::get($request->input('id'));

        if ($session) {
            $session->end();
            return response()->json(['message' => 'Session ended successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function lock(Request $request): JsonResponse
    {
        $session = Session::get($request->input('id'));

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
        $session = Session::get($request->input('id'));
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
}
