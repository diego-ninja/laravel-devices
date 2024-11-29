<?php

namespace Ninja\DeviceTracker;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\SessionTransport;
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
     * @throws SessionNotFoundException
     */
    public function current(): ?Session
    {
        return Session::current();
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

    public function end(?StorableId $sessionId = null, ?Authenticatable $user = null): bool
    {
        $session = Session::byUuid($sessionId);
        if (! $session) {
            return false;
        }

        return $session->end(
            user: $user,
        );
    }

    /**
     * @throws SessionNotFoundException
     */
    public function renew(Authenticatable $user): bool
    {
        return Session::current()->renew($user);
    }

    /**
     * @throws SessionNotFoundException
     */
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
        if (! $current) {
            return $this->start();
        }

        if (Config::get('devices.start_new_session_on_login')) {
            $current->end($user);

            return $this->start();
        }

        $current->renew($user);

        return $current;
    }

    /**
     * @throws Exception
     */
    public function inactive(?Authenticatable $user = null): bool
    {
        return $user?->inactive() ?? false;
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
            SessionTransport::forget();
            Session::destroy(session_uuid());
        }
    }
}
