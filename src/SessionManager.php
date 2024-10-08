<?php

namespace Ninja\DeviceTracker;

use Config;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Traits\HasDevices;

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

    public function end(?StorableId $sessionId = null, ?Authenticatable $user = null, bool $forgetSession = false): bool
    {
        $session = Session::findByUuid($sessionId);
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
     * @throws DeviceNotFoundException
     */
    public function refresh(): Session
    {
        $current = Session::current();
        if (!$current) {
            return $this->start();
        }

        if (Config::get('devices.start_new_session_on_login')) {
            $current->end(true);
            return $this->start();
        }

        $current->renew();

        return $current;
    }

    /**
     * @throws Exception
     */
    public function inactive(Authenticatable $user = null): bool
    {
        $uses = in_array(HasDevices::class, class_uses($user));
        if ($uses) {
            return $user?->inactive() ?? false;
        }

        throw new Exception('Authenticatable instance must use HasDevices trait');
    }

    /**
     * @throws SessionNotFoundException
     */
    public function block(StorableId $sessionId): bool
    {
        $session = Session::findByUuidOrFail($sessionId);
        return $session->block();
    }

    /**
     * @throws SessionNotFoundException
     */
    public function blocked(StorableId $sessionId): bool
    {
        $session = Session::findByUuidOrFail($sessionId);
        return $session->blocked();
    }

    /**
     * @throws SessionNotFoundException
     */
    public function locked(StorableId $sessionId): bool
    {
        $session = Session::findByUuidOrFail($sessionId);
        return $session->locked();
    }

    public function forgot(): bool
    {
        return !SessionFacade::has(Session::DEVICE_SESSION_ID);
    }

    public function sessionUuid(): ?StorableId
    {
        return Session::sessionUuid();
    }

    public function delete(): void
    {
        if ($this->sessionUuid() !== null) {
            Session::destroy($this->sessionUuid());
            SessionFacade::forget(Session::DEVICE_SESSION_ID);
        }
    }
}
