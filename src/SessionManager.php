<?php

namespace Ninja\DeviceTracker;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Traits\HasDevices;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Ramsey\Uuid\UuidInterface;
use Random\RandomException;

final readonly class SessionManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @throws DeviceNotFoundException
     */
    public function start(): Session
    {
        $device = Device::current();
        if ($device->hijacked()) {
            throw new DeviceNotFoundException('Device is flagged as hijacked.');
        }

        return Session::start(device: $device);
    }

    public function end(?UuidInterface $sessionId = null, ?Authenticatable $user = null, bool $forgetSession = false): bool
    {
        $session = Session::get($sessionId);
        if (!$session) {
            return false;
        }

        return $session->end(
            forgetSession: $forgetSession,
            user: $user,
        );
    }

    public function renew(): bool
    {
        return Session::current()->renew();
    }

    public function restart(Request $request): bool
    {
        return Session::current()->restart($request);
    }

    /**
     * @throws \Exception
     */
    public function isInactive(Authenticatable $user = null): bool
    {
        $uses = in_array(HasDevices::class, class_uses($user));
        if ($uses) {
            return $user?->isInactive() ?? false;
        }

        throw new \Exception('Authenticatable instance must use HasDevices trait');
    }

    public function block(UuidInterface $sessionId): bool
    {
        $session = Session::get($sessionId);
        return $session->block();
    }

    public function blocked(UuidInterface $sessionId): bool
    {
        $session = Session::get($sessionId);
        return $session->blocked();
    }

    public function locked(UuidInterface $sessionId): bool
    {
        $session = Session::get($sessionId);
        return $session->locked();
    }

    public function forgot(): bool
    {
        return !SessionFacade::has(Session::DEVICE_SESSION_ID);
    }

    public function sessionId(): ?int
    {
        return SessionFacade::get(Session::DEVICE_SESSION_ID);
    }

    public function delete(): void
    {

        if ($this->sessionId() != null) {
            Session::destroy($this->sessionId());
            SessionFacade::forget(Session::DEVICE_SESSION_ID);
        }
    }
}
