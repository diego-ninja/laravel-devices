<?php

namespace Ninja\DeviceTracker;

use Illuminate\Foundation\Application;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Illuminate\Support\Facades\Session as SessionFacade;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class DeviceTracker
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function startSession(): Session
    {
        return Session::start();
    }

    public function endSession(bool $forgetSession = false): bool
    {
        return Session::end($forgetSession);
    }

    public function renewSession(): bool
    {
        return Session::renew();
    }

    /**
     * @param $request
     * @return bool
     */
    public function refreshSession($request): bool
    {
        return Session::refreshSes($request);
    }

    /**
     * @param $request
     * @return bool
     */
    public function logSession($request): bool
    {
        return Session::log($request);
    }

    /**
     * @param null $user
     * @return bool
     */
    public function isSessionInactive($user = null): bool
    {
        return Session::isInactive($user);
    }

    /**
     * @param $sessionId
     * @return bool
     */
    public function blockSession($sessionId): bool
    {
        return Session::blockById($sessionId);
    }

    public function sessionRequests($sessionId)
    {
        try {
            $session = Session::findOrFail($sessionId);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        return $session->requests;
    }
    /**
     * @return bool
     */
    public function isSessionBlocked(): bool
    {
        return Session::isBlocked();
    }

    /**
     * @return bool
     */
    public function isSessionLocked(): bool
    {
        return Session::isLocked();
    }

    /**
     * @return int|null
     */
    public function lockSessionByCode(): ?int
    {
        return Session::lockByCode();
    }

    /**
     * @param $code
     * @return bool
     */
    public function unlockSessionByCode($code): bool
    {
        return Session::unlockByCode($code);
    }

    /**
     * @return bool
     */
    public function isUserDevice(): bool
    {
        return Device::isUserDevice();
    }

    public function deleteDevice($id): int
    {
        return Device::destroy($id);
    }

    public function addUserDevice(): bool
    {
        return Device::addUserDevice();
    }

    public function forgotSession(): bool
    {
        return !SessionFacade::has('dbsession.id');
    }

    public function sessionId(): ?int
    {
        return SessionFacade::get('dbsession.id');
    }

    public function deleteSession(): void
    {

        if ($this->sessionId() != null) {
            Session::destroy($this->sessionId());
            SessionFacade::forget('dbsession.id');
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
