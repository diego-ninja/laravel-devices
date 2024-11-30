<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Events\Google2FAFailed;
use Ninja\DeviceTracker\Events\Google2FASuccess;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

/**
 * @authenticated
 */
final class TwoFactorController extends Controller
{
    private ?Authenticatable $user;

    public function __construct()
    {
        $this->user = user();
    }

    public function code(): JsonResponse
    {
        if (! $this->user?->google2faEnabled()) {
            return response()->json(['message' => 'Two factor authentication is not enabled for current user'], 400);
        }

        return response()->json([
            'code' => $this->user->google2faQrCode(Config::get('devices.google_2fa_qr_format')),
        ]);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function verify(Request $request): JsonResponse
    {
        if (! $this->user?->google2faEnabled()) {
            return response()->json(['message' => 'Two factor authentication is not enabled for current user'], 400);
        }

        $code = $request->input('code');
        if (! $code) {
            return response()->json(['message' => 'Authenticator code is required'], 400);
        }

        $valid = app(Google2FA::class)
            ->verifyKeyNewer(
                secret: $this->user->google2fa->secret(),
                key: $code,
                oldTimestamp: $this->user->google2fa->last_sucess_at->timestamp ?? 0
            );

        if ($valid !== false) {
            $this->user->google2fa->success();
            event(new Google2FASuccess($this->user));

            return response()->json(['message' => 'Two factor authentication successful']);
        } else {
            event(new Google2FAFailed($this->user));

            return response()->json(['message' => 'Two factor authentication failed'], 400);
        }
    }

    public function disable(): JsonResponse
    {
        if (! $this->user?->google2faEnabled()) {
            return response()->json(['message' => 'Two factor authentication is not enabled for current user'], 400);
        }

        $this->user->google2fa->disable();

        return response()->json(['message' => 'Two factor authentication disabled for current user']);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function enable(): JsonResponse
    {
        if ($this->user?->google2faEnabled()) {
            return response()->json(['message' => 'Two factor authentication already for current user']);
        }

        $this->user?->enable2fa(
            secret: app(Google2FA::class)->generateSecretKey()
        );

        return response()->json(['message' => 'Two factor authentication enabled for current user']);
    }
}
