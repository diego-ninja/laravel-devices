<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Events\SessionBlockedEvent;
use Ninja\DeviceTracker\Events\SessionFinishedEvent;
use Ninja\DeviceTracker\Events\SessionStartedEvent;
use Ninja\DeviceTracker\Events\SessionUnblockedEvent;
use Ninja\DeviceTracker\Events\SessionUnlockedEvent;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;
use Ninja\DeviceTracker\Modules\Tracking\Models\Relations\HasManyEvents;
use Ninja\DeviceTracker\Traits\PropertyProxy;

/**
 * Class Session
 *
 * @package Ninja\DeviceManager\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property integer                      $id                     unsigned int
 * @property StorableId                   $uuid                   uuid
 * @property integer                      $user_id                unsigned int
 * @property StorableId                   $device_uuid            string
 * @property string                       $ip                     string
 * @property Location                     $location               json
 * @property SessionStatus                $status                 string
 * @property integer                      $blocked_by             unsigned int
 * @property Metadata                     $metadata               json
 * @property Carbon                       $started_at             datetime
 * @property Carbon                       $finished_at            datetime
 * @property Carbon                       $blocked_at             datetime
 * @property Carbon                       $unlocked_at            datetime
 * @property Carbon                       $last_activity_at       datetime
 *
 * @property Session                      $session
 */
class Session extends Model implements Cacheable
{
    use PropertyProxy;

    protected $table = 'device_sessions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'device_uuid',
        'ip',
        'location',
        'status',
        'metadata',
        'started_at',
        'last_activity_at',
    ];


    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'uuid', 'device_uuid');
    }

    public function user(): HasOne
    {
        return $this->hasOne(Config::get("devices.authenticatable_class"), 'id', 'user_id');
    }

    public function events(): HasManyEvents
    {
        $instance = $this->newRelatedInstance(Event::class);

        return new HasManyEvents(
            query: $instance->newQuery(),
            parent: $this,
            foreignKey: 'session_uuid',
            localKey: 'uuid'
        );
    }

    public function uuid(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => SessionIdFactory::from($value),
            set: fn(StorableId $value) => (string) $value,
        );
    }

    public function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => SessionStatus::from($value),
            set: fn(SessionStatus $value) => $value->value,
        );
    }

    public function location(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Location::fromArray(json_decode($value, true)),
            set: fn(Location $value) => $value->json(),
        );
    }

    public function metadata(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? Metadata::from(json_decode($value, true)) : new Metadata([]),
            set: fn(Metadata $value) => $value->json()
        );
    }


    public static function start(Device $device, ?Authenticatable $user = null): Session
    {
        $now = Carbon::now();

        if (App::environment("local")) {
            $development_ips = Config::get('devices.development_ip_pool', []);
            shuffle($development_ips);
            $ip = $development_ips[0];
        } else {
            $ip = request()->ip();
        }

        $location = app(LocationProvider::class)->locate($ip);

        if (!Config::get('devices.allow_device_multi_session')) {
            self::endPreviousSessions($device, $user ?? Auth::user());
        }

        $session = self::create([
            'user_id' => Auth::user()->id,
            'uuid' => SessionIdFactory::generate(),
            'device_uuid' => $device->uuid,
            'ip' => $ip,
            'location' => $location,
            'status' => self::initialStatus($device),
            'started_at' => $now,
            'last_activity_at' => $now,
        ]);

        event(new SessionStartedEvent($session, Auth::user()));

        return $session;
    }

    private static function initialStatus(Device $device): SessionStatus
    {
        if (!Auth::user()?->google2faEnabled()) {
            return SessionStatus::Active;
        } else {
            return $device->verified() ? SessionStatus::Active : SessionStatus::Locked;
        }
    }

    private static function endPreviousSessions(Device $device, Authenticatable $user): void
    {
        $previousSessions = self::where('device_uuid', $device->uuid)
            ->where('user_id', $user->id)
            ->whereNull('finished_at')
            ->get();

        foreach ($previousSessions as $session) {
            $session->end(forgetSession: true);
        }
    }

    public function event(EventType $type, Metadata $metadata): Event
    {
        return Event::create([
            'device_uuid' => $this->device_uuid,
            'session_uuid' => $this->uuid,
            'type' => $type,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'occurred_at' => now(),
        ]);
    }

    public function end(bool $forgetSession = false, ?Authenticatable $user = null): bool
    {
        $this->status = SessionStatus::Finished;
        $this->finished_at = Carbon::now();

        if ($this->save()) {
            event(new SessionFinishedEvent($this, $user ?? Auth::user()));
            return true;
        }

        SessionTransport::forget();

        return false;
    }

    public function renew(?Authenticatable $user = null): bool
    {
        $this->last_activity_at = Carbon::now();
        $this->status = SessionStatus::Active;
        $this->finished_at = null;

        if ($user) {
            $this->device->users()->updateExistingPivot($user->id, ['last_activity_at' => $this->last_activity_at]);
        }

        return $this->save();
    }

    public function restart(Request $request): bool
    {
        foreach (Config::get('devices.ignore_restart', []) as $ignore) {
            if ($this->shouldIgnoreRestart($request, $ignore)) {
                return false;
            }
        }

        return $this->renew($request->user(Config::get('devices.auth_guard')));
    }

    private function shouldIgnoreRestart(Request $request, array $ignore): bool
    {
        return ($request->route()->getName() === $ignore['route'] || $request->route()->getUri() === $ignore['route'])
            && $request->route()->methods()[0] == $ignore['method'];
    }

    public function block(?Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        $this->status = SessionStatus::Blocked;
        $this->blocked_by = $user->id;


        if ($this->save()) {
            event(new SessionBlockedEvent($this, $user));
            return true;
        }

        return false;
    }

    public function unblock(?Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        if ($this->status !== SessionStatus::Blocked) {
            return false;
        }

        $this->status = SessionStatus::Active;
        $this->blocked_by = null;


        if ($this->save()) {
            event(new SessionUnblockedEvent($this, $user));
            return true;
        }

        return false;
    }

    public function inactive(): bool
    {
        $seconds = Config::get('devices.inactivity_seconds', 1200);
        if ($seconds === 0) {
            return false;
        }

        return abs(strtotime($this->last_activity_at) - strtotime(now())) > $seconds;
    }

    public function blocked(): bool
    {
        return $this->status === SessionStatus::Blocked;
    }

    public function locked(): bool
    {
        return $this->status === SessionStatus::Locked;
    }

    public function finished(): bool
    {
        return $this->status === SessionStatus::Finished;
    }

    public function unlock(): void
    {
        if ($this->status === SessionStatus::Locked) {
            $this->status = SessionStatus::Active;
            $this->unlocked_at = Carbon::now();

            if ($this->save()) {
                event(new SessionUnlockedEvent($this, Auth::user()));
            }
        }
    }

    public function key(): string
    {
        return SessionCache::key($this->uuid);
    }

    public function ttl(): ?int
    {
        return null;
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function byUuid(StorableId|string $uuid, bool $cached = true): ?self
    {
        if (is_string($uuid)) {
            $uuid = SessionIdFactory::from($uuid);
        }

        if (!$cached) {
            return self::where('uuid', $uuid->toString())->first();
        }

        return SessionCache::remember(
            key: SessionCache::key($uuid),
            callback: function () use ($uuid) {
                return self::where('uuid', $uuid->toString())->first();
            }
        );
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function byUuidOrFail(StorableId|string $uuid): self
    {
        return self::byUuid($uuid) ?? throw SessionNotFoundException::withSession($uuid);
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function current(): ?Session
    {
        if (!session_uuid()) {
            return null;
        }

        return self::byUuid(session_uuid());
    }

    public static function boot(): void
    {
        parent::boot();

        static::created(function (Session $session) {
            SessionCache::forget($session);
            SessionCache::put($session);
        });

        static::updated(function (Session $session) {
            SessionCache::forget($session);
            SessionCache::put($session);
        });
    }
}
