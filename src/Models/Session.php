<?php

namespace Ninja\DeviceTracker\Models;

use App;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Cache\SessionCache;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Contracts\LocationProvider;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DTO\Location;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Events\SessionBlockedEvent;
use Ninja\DeviceTracker\Events\SessionFinishedEvent;
use Ninja\DeviceTracker\Events\SessionStartedEvent;
use Ninja\DeviceTracker\Events\SessionUnblockedEvent;
use Ninja\DeviceTracker\Events\SessionUnlockedEvent;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Factories\SessionIdFactory;

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
 * @property boolean                      $block                  boolean
 * @property integer                      $blocked_by             unsigned int
 * @property string                       $auth_secret            string
 * @property integer                      $auth_timestamp         unsigned int
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
    public const  DEVICE_SESSION_ID = 'session.id';

    protected $table = 'device_sessions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'device_uuid',
        'ip',
        'location',
        'status',
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

        SessionFacade::put(self::DEVICE_SESSION_ID, $session->uuid);
        SessionStartedEvent::dispatch($session, Auth::user());

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

    public function end(bool $forgetSession = false, ?Authenticatable $user = null): bool
    {
        if ($forgetSession) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);
        }

        $this->status = SessionStatus::Finished;
        $this->finished_at = Carbon::now();

        if ($this->save()) {
            SessionFinishedEvent::dispatch($this, $user ?? Auth::user());
            return true;
        }

        return false;
    }

    public function renew(): bool
    {
        $this->last_activity_at = Carbon::now();
        $this->status = SessionStatus::Active;
        $this->finished_at = null;

        return $this->save();
    }

    public function restart(Request $request): bool
    {
        foreach (Config::get('devices.ignore_restart', []) as $ignore) {
            if ($this->shouldIgnoreRestart($request, $ignore)) {
                return false;
            }
        }

        return $this->renew();
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
            SessionBlockedEvent::dispatch($this, $user);
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
            SessionUnblockedEvent::dispatch($this, $user);
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
                SessionUnlockedEvent::dispatch($this, Auth::user());
            }
        }
    }

    public function key(): string
    {
        return sprintf('%s:%s', SessionCache::KEY_PREFIX, $this->uuid);
    }

    public function ttl(): ?int
    {
        return null;
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function findByUuid(StorableId|string $uuid): ?self
    {
        if (is_string($uuid)) {
            $uuid = SessionIdFactory::from($uuid);
        }

        return SessionCache::remember($uuid->toString(), function () use ($uuid) {
            return self::where('uuid', $uuid->toString())->first();
        });
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function findByUuidOrFail(StorableId|string $uuid): self
    {
        return self::findByUuid($uuid) ?? throw SessionNotFoundException::withSession($uuid);
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function current(): ?Session
    {
        if (!self::sessionUuid()) {
            return null;
        }

        return self::findByUuid(self::sessionUuid());
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


    public static function sessionUuid(): ?StorableId
    {
        $id = SessionFacade::get(self::DEVICE_SESSION_ID);
        return $id ? SessionIdFactory::from($id) : null;
    }
}
