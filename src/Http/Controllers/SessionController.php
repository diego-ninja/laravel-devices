<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Http\Resources\SessionResource;
use Ninja\DeviceTracker\Models\Session;
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
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $session->renew();
            return response()->json(['message' => 'Session renewed successfully']);
        }

        return response()->json(['message' => 'Session not found'], 404);
    }


    /**
     * @throws InvalidAlgorithmException
     */
    public function lock(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            if (!$session->locked() && $session->lockWith2FA()) {
                return response()->json(
                    [
                        'message'    => 'Session locked successfully',
                        '2fa_qr_image' => $session->user()->get2FAQRCode()
                    ]
                );
            } else {
                return response()->json([
                    'message' => 'Session already locked',
                    '2fa_qr_image' => $session->get2FAQRCode()
                ], 400);
            }
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    public function unlock(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);
        $code = $request->input('code');

        if ($session) {
            if ($session->unlock($code)) {
                return response()->json(['message' => 'Session unlocked successfully']);
            } else {
                return response()->json(['message' => 'Invalid code'], 401);
            }
        }

        return response()->json(['message' => 'Session not found'], 404);
    }

    /**
     * @throws RandomException
     */
    public function refresh(Request $request, string $id): JsonResponse
    {
        $session = $this->findUserSession($request, $id);

        if ($session) {
            $code = $session->refreshCode();
            if ($code) {
                return response()->json(
                    [
                        'message'    => 'Session refreshed successfully',
                        'login_code' => $code
                    ]
                );
            } else {
                return response()->json(['message' => 'Unable to refresh code for session'], 400);
            }
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
        return $request
            ->user(Config::get('devices.auth_guard'))
            ->sessions()
            ->with("device")
            ->get();
    }

    private function findUserSession(Request $request, string $id): ?Session
    {
        return $request
            ->user(Config::get('devices.auth_guard'))
            ->sessions()
            ->with("device")
            ->where('uuid', Uuid::fromString($id))
            ->first();
    }
}
