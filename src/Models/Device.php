<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property string $ip string
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
    use PropertyProxy;

    protected $table = 'devices';

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
        'ip',
        'metadata',
        'source',
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
        $table = sprintf('%s_devices', str(config('devices.authenticatable_table'))->singular());
        $field = sprintf('%s_id', str(config('devices.authenticatable_table'))->singular());

        /** @var class-string<User> $authenticatable */
        $authenticatable = Config::get('devices.authenticatable_class', User::class);

        return $this->belongsToMany(
            related: $authenticatable,
            table: $table,
            foreignPivotKey: 'device_uuid',
            relatedPivotKey: $field,
            parentKey: 'uuid',
            relatedKey: 'id'
        )->withTimestamps();
    }

    public function uuid(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => DeviceIdFactory::from($value),
            set: fn (StorableId $value) => (string) $value
        );
    }

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? DeviceStatus::from($value) : DeviceStatus::Unverified,
            set: fn (DeviceStatus $value) => $value->value
        );
    }

    public function metadata(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Metadata::from(json_decode($value, true)) : new Metadata([]),
            set: fn (Metadata $value) => $value->json()
        );
    }

    public function isCurrent(): bool
    {
        return $this->uuid === device_uuid();
    }

    /**
     * @throws FingerprintDuplicatedException
     */
    public function fingerprint(string $fingerprint, ?string $cookie = null): void
    {
        try {
            $this->fingerprint = $fingerprint;
            if ($this->save()) {
                if ($cookie) {
                    if (! Cookie::has($cookie)) {
                        Cookie::queue(
                            Cookie::forever(
                                name: $cookie,
                                value: $fingerprint,
                                secure: Config::get('session.secure', false),
                                httpOnly: Config::get('session.http_only', true)
                            )
                        );
                    }
                }
                event(new DeviceFingerprintedEvent($this));
            }
        } catch (PDOException $exception) {
            throw FingerprintDuplicatedException::forFingerprint($fingerprint, Device::byFingerprint($fingerprint));
        }
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

        $this->users()->updateExistingPivot($user?->getAuthIdentifier(), [
            'device_uuid' => $this->uuid,
            'status' => DeviceStatus::Verified,
            'verified_at' => now(),
        ]);

        $this->sessions()
            ->where('status', SessionStatus::Locked)
            ->where('user_id', $user?->getAuthIdentifier())
            ->get()
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
        $this->users()->each(function ($user) use (&$status) {
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
        $deviceUser = $this->users()->where('user_id', $user?->getAuthIdentifier())->first();

        return $deviceUser && $this->status === $deviceUser->pivot->status;
    }

    public function hijack(?Authenticatable $user = null): void
    {
        $user = $user ?? user();

        $this->hijacked_at = now();

        $this->users()->updateExistingPivot($user?->getAuthIdentifier(), [
            'status' => DeviceStatus::Hijacked,
        ]);

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
        return $this->browser === $dto->browser->name
            && $this->browser_family === $dto->browser->family
            && $this->browser_engine === $dto->browser->engine
            && $this->platform === $dto->platform->name
            && $this->platform_family === $dto->platform->family
            && $this->device_type === $dto->device->type
            && $this->device_family === $dto->device->family
            && $this->device_model === $dto->device->model;
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
        DeviceDTO $data
    ): ?self {
        $device = self::byUuid($deviceUuid, false);
        if ($device) {
            return $device;
        }

        try {
            $device = self::create([
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
                'ip' => request()->ip(),
                'metadata' => new Metadata([]),
                'source' => $data->source,
            ]);

            /** @var Device $device */
            if ($device) {
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

    public static function current(): ?self
    {
        if (Config::get('devices.fingerprinting_enabled')) {
            if (fingerprint()) {
                return self::byFingerprint(fingerprint());
            }
        }

        if (device_uuid()) {
            return self::byUuid(device_uuid());
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
     * @return Collection<int, string>
     */
    public static function byStatus(): Collection
    {
        return self::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    /**
     * @return Collection<int, Device|Model>
     */
    public static function orphans(): Collection
    {
        return self::doesntHave('users')
            ->doesntHave('sessions')
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
}
