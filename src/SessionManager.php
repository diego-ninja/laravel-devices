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
use Ninja\DeviceTracker\Facades\DeviceManager;
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
        $device = DeviceManager::current();
        if ($device->hijacked()) {
            throw new DeviceNotFoundException('Device is flagged as hijacked.');
        }

        return Session::start(device: $device);
    }

    /**
     * @throws SessionNotFoundException
     */
    public function end(?StorableId $sessionId = null, ?Authenticatable $user = null, bool $forgetSession = false): bool
    {
        $session = Session::byUuid($sessionId);
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
     * @throws SessionNotFoundException
     */
    public function refresh(?Authenticatable $user = null): Session
    {
        $current = Session::current();
        if (!$current) {
            return $this->start();
        }

        if (Config::get('devices.start_new_session_on_login')) {
            $current->end(true, $user);
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
        $session = Session::byUuidOrFail($sessionId);
        return $session->block();
    }

    /**
     * @throws SessionNotFoundException
     */
    public function blocked(StorableId $sessionId): bool
    {
        $session = Session::byUuidOrFail($sessionId);
        return $session->blocked();
    }

    /**
     * @throws SessionNotFoundException
     */
    public function locked(StorableId $sessionId): bool
    {
        $session = Session::byUuidOrFail($sessionId);
        return $session->locked();
    }

    public function delete(): void
    {
        if (session_uuid() !== null) {
            Session::destroy(session_uuid());
            SessionFacade::forget(Session::DEVICE_SESSION_ID);
        }
    }
}
