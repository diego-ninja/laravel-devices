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

final readonly class SessionManager
{
    public Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function current(): ?Session
    {
        return Session::current();
    }

    /**
     * @throws DeviceNotFoundException
     */
    public function start(?Authenticatable $user = null): Session
    {
        $device = DeviceManager::current();
        if (! $device) {
            throw new DeviceNotFoundException('Device not found.');
        }

        if ($device->hijacked()) {
            throw new DeviceNotFoundException('Device is flagged as hijacked.');
        }

        return Session::start(
            device: $device,
            user: $user,
        );
    }

    public function end(?StorableId $sessionId = null, ?Authenticatable $user = null): bool
    {
        $sessionId ??= session_uuid();

        if ($sessionId === null) {
            return false;
        }

        $session = Session::byUuid($sessionId);
        if (! $session) {
            return false;
        }

        return $session->end(
            user: $user,
        );
    }

    public function renew(Authenticatable $user): ?bool
    {
        return Session::current()?->renew($user);
    }

    public function restart(Request $request): ?bool
    {
        return Session::current()?->restart($request);
    }

    /**
     * @throws DeviceNotFoundException
     */
    public function refresh(?Authenticatable $user = null): Session
    {
        $current = Session::current();
        if (! $current) {
            return $this->start($user);
        }

        if (Config::get('devices.start_new_session_on_login')) {
            $current->end($user);

            return $this->start($user);
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
