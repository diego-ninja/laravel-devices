<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ninja\DeviceTracker\Models\Session;

final class SessionController extends Controller
{
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
            $session->unlockByCode($code);
            return response()->json(['message' => 'Session unlocked successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }
}
