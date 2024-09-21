<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\LocationProvider;
use Ninja\DeviceTracker\DTO\Location;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Random\RandomException;

/**
 * Class Session
 *
 * @package Ninja\DeviceManager\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property integer                      $id                     unsigned int
 * @property UuidInterface                $uuid                   uuid
 * @property integer                      $user_id                unsigned int
 * @property UuidInterface                $device_uuid            string
 * @property string                       $ip                     string
 * @property Location                     $location               json
 * @property boolean                      $block                  boolean
 * @property integer                      $blocked_by             unsigned int
 * @property string                       $login_code             string
 * @property Carbon                       $started_at             datetime
 * @property Carbon                       $finished_at            datetime
 * @property Carbon                       $last_activity_at       datetime
 *
 * @property Session                       $session
 */
class Session extends Model
{
    public const  DEVICE_SESSION_ID = 'session.id';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_LOCKED = 'locked';

    protected $table = 'device_sessions';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'device_uuid',
        'ip',
        'location',
        'started_at',
        'last_activity_at'
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
            get: fn(string $value) => Uuid::fromString($value),
            set: fn(UuidInterface $value) => $value->toString()
        );
    }

    public function status(): string
    {
        if ($this->block) {
            return self::STATUS_BLOCKED;
        }

        if ($this->finished_at !== null) {
            return self::STATUS_FINISHED;
        }

        if ($this->login_code !== null) {
            return self::STATUS_LOCKED;
        }

        if (abs(strtotime($this->last_activity_at) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200)) {
            return self::STATUS_INACTIVE;
        }

        return self::STATUS_ACTIVE;
    }

    public function location(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Location::fromArray(json_decode($value, true)),
            set: fn(Location $value) => $value->json()
        );
    }

    public function start(): Session
    {
        $deviceId = Device::getDeviceUuid();
        $userId = Auth::user()->id;
        $now = Carbon::now();

        $ip = request()->ip();
        $location = app(LocationProvider::class)->fetch($ip);

        if ($deviceId && !Config::get('devices.allow_device_multi_session')) {
            $this->endPreviousSessions($deviceId, $userId, $now);
        }

        $session = $this->create([
            'user_id' => $userId,
            'uuid' => Uuid::uuid7(),
            'device_uuid' => $deviceId,
            'ip' => $ip,
            'location' => $location,
            'started_at' => $now,
            'last_activity_at' => $now
        ]);

        SessionFacade::put(self::DEVICE_SESSION_ID, $session->uuid);

        return $session;
    }

    private function endPreviousSessions($deviceId, $userId, $now): void
    {
        self::where('device_uid', $deviceId)
            ->where('user_id', $userId)
            ->whereNull('finished_at')
            ->update(['finished_at' => $now]);
    }

    public function end(bool $forgetSession = false): bool
    {
        if ($forgetSession) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);
        }

        $this->finished_at = Carbon::now();
        return $this->save();
    }

    public function renew(): bool
    {
        $this->last_activity_at = Carbon::now();
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

    public function isInactive(): bool
    {
        return abs(strtotime($this->last_activity_at) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200);
    }

    public function block(): bool
    {
        $this->block = true;
        $this->blocked_by = Auth::user()->id;
        return $this->save();
    }

    public function isBlocked(): bool
    {
        return $this->block;
    }

    public function isLocked(): bool
    {
        return $this->login_code !== null;
    }

    public function loginCode(): ?string
    {
        return $this->login_code;
    }

    /**
     * @throws RandomException
     */
    public function lockByCode(): ?int
    {
        $code = random_int(100000, 999999);
        $this->login_code = sha1($code);

        $this->save();

        return $code;
    }

    /**
     * @throws RandomException
     */
    public function refreshCode(): ?int
    {
        // TODO: implement code generator class
        $code = random_int(100000, 999999);
        $this->login_code = sha1($code);
        $this->started_at = Carbon::now();

        $this->save();

        return $code;
    }

    public function unlockByCode(int $code): bool
    {
        if (time() - strtotime($this->started_at) > Config::get('devices.security_code_lifetime', 1200)) {
            return false;
        }

        if (sha1($code) === $this->login_code) {
            $this->login_code = null;
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * @throws SessionNotFoundException
     */
    public static function findByUuid(UuidInterface|string $uuid): ?self
    {
        if (is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }

        $session = self::where('uuid', $uuid->toString())->first();
        if (!$session) {
            throw SessionNotFoundException::withSession($uuid);
        }

        return $session;
    }

    public static function current(): ?Session
    {
        return self::get();
    }

    public static function get(?UuidInterface $sessionId = null): ?Session
    {
        $sessionId = $sessionId ?? self::sessionId();

        try {
            return self::findByUuid($sessionId);
        } catch (SessionNotFoundException $e) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);

            Log::warning(
                sprintf('Session %s not found: %s', $sessionId, $e->getMessage())
            );

            return null;
        }
    }

    private static function sessionId(): ?UuidInterface
    {
        $id = SessionFacade::get(self::DEVICE_SESSION_ID);
        return $id ? Uuid::fromString($id) : null;
    }
}
