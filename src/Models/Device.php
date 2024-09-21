<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class DeviceManager
 *
 * @package Ninja\DeviceManager\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property string                       $id                     unsigned string
 * @property UuidInterface                $uuid                   string
 * @property integer                      $user_id                unsigned string
 * @property string                       $browser                string
 * @property string                       $browser_version        string
 * @property string                       $platform               string
 * @property string                       $platform_version       string
 * @property boolean                      $mobile                 boolean
 * @property string                       $device                 string
 * @property string                       $device_type            string
 * @property boolean                      $robot                  boolean
 * @property string                       $source                 string
 * @property Carbon                       $created_at             datetime
 * @property Carbon                       $updated_at             datetime
 *
 * @property Session                       $session
 */
class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'uuid',
        'user_id',
        'browser',
        'browser_version',
        'platform',
        'platform_version',
        'mobile',
        'device',
        'device_type',
        'robot',
        'source'
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'device_uuid', 'uuid');
    }

    public function user(): HasOne
    {
        return $this->hasOne(Config::get("devices.authenticatable_class"), 'id', 'user_id');
    }

    public function uuid(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Uuid::fromString($value),
            set: fn(UuidInterface $value) => $value->toString()
        );
    }

    public function isCurrent(): bool
    {
        return $this->uuid->toString() === self::getDeviceUuid()?->toString();
    }

    /**
     * @throws DeviceNotFoundException
     */
    public static function findByUuid(UuidInterface|string $uuid): ?self
    {
        if (is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }

        $session = self::where('uuid', $uuid->toString())->first();
        if (!$session) {
            throw DeviceNotFoundException::withDevice($uuid);
        }

        return $session;
    }

    public static function getDeviceUuid(): ?UuidInterface
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        return Cookie::has($cookieName) ? Uuid::fromString(Cookie::get($cookieName)) : null;
    }
}
