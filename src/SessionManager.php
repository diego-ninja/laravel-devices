<?php

namespace Ninja\DeviceTracker;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Models\Session;

final readonly class SessionManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function start(): Session
    {
        return Session::start();
    }

    public function end(bool $forgetSession = false): bool
    {
        return Session::end($forgetSession);
    }

    public function renew(): bool
    {
        return Session::renew();
    }

    public function restart(Request $request): bool
    {
        return Session::restart($request);
    }

    public function isInactive(Authenticatable $user = null): bool
    {
        return Session::isInactive($user);
    }

    public function block(string $sessionId): bool
    {
        return Session::blockById($sessionId);
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return Session::isBlocked();
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return Session::isLocked();
    }

    /**
     * @return int|null
     */
    public function lockByCode(): ?int
    {
        return Session::lockByCode();
    }

    /**
     * @param $code
     * @return bool
     */
    public function unlockByCode($code): bool
    {
        return Session::unlockByCode($code);
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

    public function securityCode(): ?string
    {
        return Session::loginCode();
    }

    public function refreshSecurityCode(): ?int
    {
        return Session::refreshCode();
    }
}
