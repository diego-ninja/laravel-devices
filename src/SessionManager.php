<?php

namespace Ninja\DeviceTracker;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Traits\HasDevices;
use Ramsey\Uuid\UuidInterface;
use Random\RandomException;

final readonly class SessionManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function start(): Session
    {
        return (new Session())->start();
    }

    public function end(?UuidInterface $sessionId = null, bool $forgetSession = false): bool
    {
        $session = Session::get($sessionId);
        return $session->end($forgetSession);
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

    public function isBlocked(UuidInterface $sessionId): bool
    {
        $session = Session::get($sessionId);
        return $session->isBlocked();
    }



    public function isLocked(UuidInterface $sessionId): bool
    {
        $session = Session::get($sessionId);
        return $session->isLocked();
    }

    /**
     * @throws RandomException
     */
    public function lock(UuidInterface $sessionId): ?int
    {
        $session = Session::get($sessionId);
        return $session->lockByCode();
    }

    public function unlock(UuidInterface $sessionId, int $code): bool
    {
        $session = Session::get($sessionId);
        return $session->unlockByCode($code);
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

    public function securityCode(UuidInterface $sessionId): ?string
    {
        $session = Session::get($sessionId);
        return $session->login_code;
    }

    /**
     * @throws RandomException
     */
    public function refreshSecurityCode(UuidInterface $sessionId): ?int
    {
        $session = Session::get($sessionId);
        return $session->refreshCode();
    }
}
