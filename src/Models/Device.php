<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Cache\DeviceCache;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Database\Factories\DeviceFactory;
use Ninja\DeviceTracker\DTO\Device as DeviceDTO;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Events\DeviceDeletedEvent;
use Ninja\DeviceTracker\Events\DeviceFingerprintedEvent;
use Ninja\DeviceTracker\Events\DeviceHijackedEvent;
use Ninja\DeviceTracker\Events\DeviceUpdatedEvent;
use Ninja\DeviceTracker\Events\DeviceVerifiedEvent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\FingerprintDuplicatedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Relations\HasManySessions;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;
use Ninja\DeviceTracker\Modules\Tracking\Models\Relations\HasManyEvents;
use Ninja\DeviceTracker\Traits\PropertyProxy;
use PDOException;

/**
 * Class Device
 *
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Builder<Device>
 *
 * @property int $id unsigned int
 * @property StorableId $uuid string
 * @property string $fingerprint string
 * @property DeviceStatus $status string
 * @property string $browser string
 * @property string $browser_version string
 * @property string $browser_family string
 * @property string $browser_engine string
 * @property string $platform string
 * @property string $platform_version string
 * @property string $platform_family string
 * @property string $device_type string
 * @property string $device_family string
 * @property string $device_model string
 * @property string $grade string
 * @property string $source string
 * @property string|null $device_id string|null
 * @property string|null $advertising_id string|null
 * @property string|null $client_fingerprint string|null
 * @property Metadata $metadata json
 * @property Carbon $created_at datetime
 * @property Carbon $updated_at datetime
 * @property Carbon $verified_at datetime
 * @property Carbon $hijacked_at datetime
 * @property Carbon $risk_assessed_at datetime
 * @property-read Collection<int, Session> $sessions
 * @property-read Collection<int, Event> $events
 * @property-read Collection<int, User> $users
 */
class Device extends Model implements Cacheable
{
    use HasFactory;
    use PropertyProxy;

    protected $table = 'devices';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'verified_at' => 'datetime',
        'hijacked_at' => 'datetime',
        'risk_assessed_at' => 'datetime',
    ];

    protected $fillable = [
        'uuid',
        'fingerprint',
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
        'metadata',
        'source',
        'device_id',
        'advertising_id',
        'client_fingerprint',
    ];

    public function sessions(): HasManySessions
    {
        $instance = $this->newRelatedInstance(Session::class);

        return new HasManySessions(
            query: $instance->newQuery(),
            parent: $this,
            foreignKey: 'device_uuid',
            localKey: 'uuid'
        );
    }

    public function events(): HasManyEvents
    {
        $instance = $this->newRelatedInstance(Event::class);

        return new HasManyEvents(
            query: $instance->newQuery(),
            parent: $this,
            foreignKey: 'device_uuid',
            localKey: 'uuid'
        );
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        /** @var class-string<User> $authenticatable */
        $authenticatable = Config::get('devices.authenticatable_class', User::class);

        return $this->belongsToMany(
            related: $authenticatable,
            table: 'device_sessions',
            foreignPivotKey: 'device_uuid',
            relatedPivotKey: 'user_id',
            parentKey: 'uuid',
            relatedKey: 'id'
        );
    }

    /**
     * @return Attribute<Closure, Closure>
     */
    public function uuid(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => DeviceIdFactory::from($value),
            set: fn (StorableId $value) => (string) $value
        );
    }

    /**
     * @return Attribute<Closure, Closure>
     */
    public function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? DeviceStatus::from($value) : DeviceStatus::Unverified,
            set: fn (DeviceStatus $value) => $value->value
        );
    }

    /**
     * @return Attribute<Closure, Closure>
     */
    public function metadata(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value !== null ? Metadata::from(json_decode($value, true)) : new Metadata([]),
            set: fn (Metadata $value) => $value->json()
        );
    }

    public function isCurrent(): bool
    {
        return (string) $this->uuid === (string) device_uuid();
    }

    public function fingerprint(string $fingerprint, ?string $cookie = null): void
    {
        DB::transaction(function () use ($fingerprint, $cookie) {
            try {
                $this->fingerprint = $fingerprint;
                if ($this->save()) {
                    if ($cookie !== null) {
                        Cookie::queue(Cookie::forever(
                            name: $cookie,
                            value: $fingerprint,
                            secure: Config::get('session.secure', false),
                            httpOnly: Config::get('session.http_only', true)
                        ));
                    }
                    event(new DeviceFingerprintedEvent($this));
                }
            } catch (PDOException) {
                throw FingerprintDuplicatedException::forFingerprint($fingerprint, Device::byFingerprint($fingerprint));
            }
        });
    }

    public function fingerprinted(): bool
    {
        return $this->fingerprint !== null;
    }

    public function verify(?Authenticatable $user = null): void
    {
        if ($this->status === DeviceStatus::Verified) {
            return;
        }

        $user = $user ?? user();

        if (DeviceManager::userDevicesTableEnabled()) {
            $this->users()->updateExistingPivot($user?->getAuthIdentifier(), [
                'device_uuid' => $this->uuid,
                'status' => DeviceStatus::Verified,
                'verified_at' => now(),
            ]);
        }

        $this->sessions
            ->where('status', SessionStatus::Locked)
            ->where('user_id', $user?->getAuthIdentifier())
            ->each(function (Session $session) {
                $session->unlock();
            });

        $status = $this->verifiedStatus();
        $this->status = $status;

        if ($status === DeviceStatus::Verified) {
            $this->verified_at = now();
        }

        if ($this->save()) {
            event(new DeviceVerifiedEvent($this, $user));
        }
    }

    public function verifiedStatus(): DeviceStatus
    {
        $status = DeviceStatus::Unverified;
        $this->users()->each(function (Authenticatable $user) use (&$status) {
            if ($user->pivot->status === DeviceStatus::Verified) {
                $status = DeviceStatus::PartiallyVerified;
            } elseif ($user->pivot->status === DeviceStatus::Unverified && $status !== DeviceStatus::PartiallyVerified) {
                $status = DeviceStatus::Unverified;
            } else {
                $status = DeviceStatus::Verified;
            }
        });

        return $status;
    }

    public function verified(?Authenticatable $user = null): bool
    {
        $user = $user ?? user();
        $deviceUser = $this->users()::where('user_id', $user?->getAuthIdentifier())->first();

        return $deviceUser !== null && $this->status === $deviceUser->pivot->status;
    }

    public function hijack(?Authenticatable $user = null): void
    {
        $user = $user ?? user();

        $this->hijacked_at = now();

        if (DeviceManager::userDevicesTableEnabled()) {
            $this->users()->updateExistingPivot($user?->getAuthIdentifier(), [
                'status' => DeviceStatus::Hijacked,
            ]);
        }

        foreach ($this->sessions as $session) {
            $session->block();
        }

        if ($this->save()) {
            event(new DeviceHijackedEvent($this, $user));
        }
    }

    public function hijacked(): bool
    {
        return $this->hijacked_at !== null;
    }

    public function forget(): ?bool
    {
        $this->sessions()->active()->each(fn (Session $session) => $session->end());

        return $this->delete();
    }

    public function label(): string
    {
        return $this->device_family.' '.$this->device_model;
    }

    public function equals(DeviceDTO $dto): bool
    {
        // Platform version should not be important
        $matchPlatform = $dto->platform->name === $this->platform
            && $dto->platform->family === $this->platform_family;

        // Browser version should not be important
        $matchBrowser = $dto->browser->name === $this->browser
            && $dto->browser->family === $this->browser_family
            && $dto->browser->engine === $this->browser_engine;

        $matchDevice = $dto->device->family === $this->device_family
            && $dto->device->model === $this->device_model
            && $dto->device->type === $this->device_type;

        $matchIds = $dto->advertisingId === $this->advertising_id
            && $dto->deviceId === $this->device_id
            && $dto->clientFingerprint === $this->client_fingerprint;

        return $matchPlatform && $matchBrowser && $matchDevice && $matchIds;
    }

    public function key(): string
    {
        return DeviceCache::key($this->uuid);
    }

    public function ttl(): ?int
    {
        return null;
    }

    public static function register(
        StorableId $deviceUuid,
        DeviceDTO $data,
    ): ?self {
        $device = self::byUuid($deviceUuid, false);
        if ($device !== null) {
            return $device;
        }

        try {
            $device = self::firstOrCreate([
                'uuid' => $deviceUuid,
            ], [
                'uuid' => $deviceUuid,
                'fingerprint' => fingerprint(),
                'browser' => $data->browser->name,
                'browser_version' => $data->browser->version,
                'browser_family' => $data->browser->family,
                'browser_engine' => $data->browser->engine,
                'platform' => $data->platform->name,
                'platform_version' => $data->platform->version,
                'platform_family' => $data->platform->family,
                'device_type' => $data->device->type,
                'device_family' => $data->device->family,
                'device_model' => $data->device->model,
                'grade' => $data->grade,
                'metadata' => new Metadata([]),
                'source' => $data->source,
                'client_fingerprint' => $data->clientFingerprint,
            ]);

            /** @var Device $device */
            if ($device !== null) {
                return $device;
            }
        } catch (PDOException $e) {
            Log::warning(sprintf('Unable to create device for UUID: %s (%s)', $deviceUuid, $e->getMessage()));

            return null;
        }

        return null;
    }

    public static function byUuid(StorableId|string $uuid, bool $cached = true): ?self
    {
        if (is_string($uuid)) {
            $uuid = DeviceIdFactory::from($uuid);
        }

        if (! $cached) {
            /** @var Device|null $device */
            $device = self::where('uuid', (string) $uuid)->first();

            return $device;
        }

        return DeviceCache::remember(
            key: DeviceCache::key($uuid),
            callback: fn () => self::byUuid($uuid, false)
        );
    }

    /**
     * @throws DeviceNotFoundException
     */
    public static function byUuidOrFail(StorableId|string $uuid): self
    {
        return self::byUuid($uuid) ?? throw DeviceNotFoundException::withDevice($uuid);
    }

    public static function byFingerprint(string $fingerprint, bool $cached = true): ?self
    {
        if (! $cached) {
            /** @var Device|null $device */
            $device = self::where('fingerprint', $fingerprint)->first();

            return $device;
        }

        return DeviceCache::remember(
            key: DeviceCache::key($fingerprint),
            callback: fn () => self::where('fingerprint', $fingerprint)->first()
        );
    }

    public static function byClientFingerprint(StorableId $clientFingerprint, bool $cached = true): ?self
    {
        if (! $cached) {
            /** @var Device|null $device */
            $device = self::where('client_fingerprint', $clientFingerprint)->first();

            return $device;
        }

        return DeviceCache::remember(
            key: DeviceCache::key($clientFingerprint),
            callback: fn () => self::where('client_fingerprint', $clientFingerprint)->first()
        );
    }

    public static function byDeviceDtoUniqueInfo(DeviceDto $deviceDto): ?self
    {
        if ($deviceDto->deviceId !== null) {
            $device = Device::query()
                ->where('device_id', $deviceDto->deviceId)
                ->where('platform', $deviceDto->platform->name)
                ->first();
            if ($device !== null) {
                return $device;
            }
        }

        if ($deviceDto->clientFingerprint !== null) {
            $device = Device::query()
                ->where('client_fingerprint', $deviceDto->clientFingerprint)
                ->first();
            if ($device !== null) {
                return $device;
            }
        }

        if ($deviceDto->advertisingId !== null) {
            $device = Device::query()
                ->where('advertising_id', $deviceDto->deviceId)
                ->where('platform', $deviceDto->platform->name)
                ->first();
            if ($device !== null) {
                return $device;
            }
        }

        return null;
    }

    public static function current(): ?self
    {
        if (config('devices.fingerprinting_enabled') === true) {
            if (fingerprint() !== null) {
                return self::byFingerprint(fingerprint());
            }
        }

        $clientFingerprint = client_fingerprint();
        if ($clientFingerprint !== null) {
            return self::byClientFingerprint($clientFingerprint);
        }

        $deviceUuid = device_uuid();
        if ($deviceUuid !== null) {
            return self::byUuid($deviceUuid);
        }

        return null;
    }

    public static function exists(StorableId|string $id): bool
    {
        if (is_string($id)) {
            $id = DeviceIdFactory::from($id);
        }

        return self::byUuid($id, false) !== null;
    }

    /**
     * @return Collection<int, Device>
     */
    public static function orphans(): Collection
    {
        return self::doesntHave('users')
            ->doesntHave('sessions')
            ->where(function ($query) {
                $query->where('status', DeviceStatus::Unverified)
                    ->orWhereNull('fingerprint');
            })
            ->where('created_at', '<', now()->subSeconds(config('devices.orphan_retention_period')))
            ->get();
    }

    public static function boot(): void
    {
        parent::boot();

        static::created(function (Device $device) {
            DeviceCache::forget($device);
            DeviceCache::put($device);

            event(new DeviceCreatedEvent($device));
        });

        self::deleted(function (Device $device) {
            DeviceCache::forget($device);

            event(new DeviceDeletedEvent($device));
        });

        static::updated(function (Device $device) {
            DeviceCache::forget($device);
            DeviceCache::put($device);

            event(new DeviceUpdatedEvent($device));
        });
    }

    protected static function newFactory()
    {
        return new DeviceFactory;
    }
}
