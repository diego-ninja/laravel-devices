<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Events\DeviceHijackedEvent;
use Ninja\DeviceTracker\Events\DeviceVerifiedEvent;
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
 * @property string                       $id                     unsigned int
 * @property UuidInterface                $uuid                   string
 * @property integer                      $user_id                unsigned int
 * @property DeviceStatus                 $status                 string
 * @property string                       $browser                string
 * @property string                       $browser_version        string
 * @property string                       $browser_family         string
 * @property string                       $browser_engine         string
 * @property string                       $platform               string
 * @property string                       $platform_version       string
 * @property string                       $platform_family        string
 * @property string                       $device_type            string
 * @property string                       $device_family          string
 * @property string                       $device_model           string
 * @property string                       $grade                  string
 * @property string                       $source                 string
 * @property string                       $ip                     string
 * @property Carbon                       $created_at             datetime
 * @property Carbon                       $updated_at             datetime
 * @property Carbon                       $verified_at            datetime
 * @property Carbon                       $hijacked_at            datetime
 *
 */
class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'uuid',
        'user_id',
        'browser',
        'browser_version',
        'browser_family',
        'browser_engine',
        'platform',
        'platform_version',
        'platform_family',
        'device_type',
        'device_family',
        'device_model',
        'grade',
        'ip',
        'source',
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

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => DeviceStatus::from($value),
            set: fn(DeviceStatus $value) => $value->value
        );
    }

    public function activeSessions(): Collection
    {
        return $this
            ->sessions()
            ->where('status', SessionStatus::Active)
            ->get();
    }

    public function isCurrent(): bool
    {
        return $this->uuid->toString() === self::getDeviceUuid()?->toString();
    }

    public function verify(): void
    {
        $this->verified_at = now();
        $this->status = DeviceStatus::Verified;

        if ($this->save()) {
            DeviceVerifiedEvent::dispatch($this, $this->user);
        }
    }

    public function verified(): bool
    {
        return $this->status === DeviceStatus::Verified;
    }

    public function hijack(?Authenticatable $user = null): void
    {
        $user = $user ?? Auth::user();

        $this->hijacked_at = now();
        $this->status = DeviceStatus::Hijacked;

        foreach ($this->sessions as $session) {
            $session->block();
        }

        if ($this->save()) {
            DeviceHijackedEvent::dispatch($this, $user);
        }
    }

    public function hijacked(): bool
    {
        return $this->status === DeviceStatus::Hijacked;
    }

    public function forget(): bool
    {
        $this->sessions->each(fn(Session $session) => $session->end(forgetSession: true));
        return $this->delete();
    }

    public function label(): string
    {
        return $this->device_family . ' ' . $this->device_model;
    }

    public static function register(
        UuidInterface $deviceUuid,
        \Ninja\DeviceTracker\DTO\Device $data,
        Authenticatable $user = null
    ): ?self {
        $device = self::create([
            'uuid' => $deviceUuid,
            'user_id' => $user->id,
            'browser' => $data->browser->name,
            'browser_version' => $data->browser->version,
            'browser_family' => $data->browser->family,
            'platform' => $data->platform->name,
            'platform_version' => $data->platform->version,
            'platform_family' => $data->platform->family,
            'device_type' => $data->device->type,
            'device_family' => $data->device->family,
            'device_model' => $data->device->model,
            'grade' => $data->grade,
            'ip' => request()->ip(),
            'source' => $data->userAgent,
        ]);

        if ($device) {
            DeviceCreatedEvent::dispatch($device, $user);
            return $device;
        }

        return null;
    }

    public static function findByUuid(UuidInterface|string $uuid): ?self
    {
        if (is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }

        return self::where('uuid', $uuid->toString())->first();
    }

    /**
     * @throws DeviceNotFoundException
     */
    public static function findByUuidOrFail(UuidInterface|string $uuid): self
    {
        return self::findByUuid($uuid) ?? throw DeviceNotFoundException::withDevice($uuid);
    }

    public static function current(): ?self
    {
        return self::findByUuid(self::getDeviceUuid());
    }

    public static function getDeviceUuid(): ?UuidInterface
    {
        $cookieName = Config::get('devices.device_id_cookie_name');
        return Cookie::has($cookieName) ? Uuid::fromString(Cookie::get($cookieName)) : null;
    }
}
