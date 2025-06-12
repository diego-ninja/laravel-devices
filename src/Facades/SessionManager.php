<?php

namespace Ninja\DeviceTracker\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Models\Session;

/**
 * @method static Session|null current()
 * @method static Session start(?Authenticatable $user = null)
 * @method static bool end(?StorableId $sessionId = null, ?Authenticatable $user = null)
 * @method static bool renew(Authenticatable $user)
 * @method static bool restart(Request $request)
 * @method static Session refresh(?Authenticatable $user = null)
 * @method static bool inactive(?Authenticatable $user = null)
 * @method static bool block(StorableId $sessionId)
 * @method static bool blocked(StorableId $sessionId)
 * @method static bool locked(StorableId $sessionId)
 * @method static void delete()
 */
final class SessionManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'session_manager';
    }
}
