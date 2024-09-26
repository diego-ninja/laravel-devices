<?php

namespace Ninja\DeviceTracker\Models;

use App;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session as SessionFacade;
use Ninja\DeviceTracker\Contracts\CodeGenerator;
use Ninja\DeviceTracker\Contracts\LocationProvider;
use Ninja\DeviceTracker\DTO\Location;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Events\SessionBlockedEvent;
use Ninja\DeviceTracker\Events\SessionFinishedEvent;
use Ninja\DeviceTracker\Events\SessionLockedEvent;
use Ninja\DeviceTracker\Events\SessionStartedEvent;
use Ninja\DeviceTracker\Events\SessionUnblockedEvent;
use Ninja\DeviceTracker\Events\SessionUnlockedEvent;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Traits\Has2FA;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidAlgorithmException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;
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
 * @property Session                       $session
 */
class Session extends Model
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
            get: fn(string $value) => Uuid::fromString($value),
            set: fn(UuidInterface $value) => $value->toString(),
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

    public static function start(Device $device): Session
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
            self::endPreviousSessions($device, $device->user);
        }

        $session = self::create([
            'user_id' => $device->user->id,
            'uuid' => Uuid::uuid7(),
            'device_uuid' => $device->uuid,
            'ip' => $ip,
            'location' => $location,
            'status' => SessionStatus::Active,
            'started_at' => $now,
            'last_activity_at' => $now,
        ]);

        SessionFacade::put(self::DEVICE_SESSION_ID, $session->uuid);
        SessionStartedEvent::dispatch($session, $device->user);

        return $session;
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

    public function isInactive(): bool
    {
        $seconds = Config::get('devices.inactivity_seconds', 1200);
        if ($seconds === 0) {
            return false;
        }

        return abs(strtotime($this->last_activity_at) - strtotime(now())) > $seconds;
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

    /**
     * @throws InvalidAlgorithmException
     */
    public function lockWith2FA(?Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (in_array(Has2FA::class, class_uses($user)) === false) {
            return false;
        }

        if ($this->status !== SessionStatus::Active) {
            return false;
        }

        $user->enable2FA(app(CodeGenerator::class)->generate());

        $this->status = SessionStatus::Locked;

        if ($this->save()) {
            SessionLockedEvent::dispatch($this, $this->secret, $user);
            return true;
        }

        return false;
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function unlock(int $code): bool
    {

        if ($this->status !== SessionStatus::Locked) {
            return false;
        }

        $valid = app(Google2FA::class)
            ->verifyKeyNewer(
                secret: $this->user()->get2FASecret(),
                key: $code,
                oldTimestamp: $this->unlocked_at->timestamp,
                window: 1
            );

        if ($valid !== false) {
            $this->auth_timestamp = $valid;
            $this->status = SessionStatus::Active;

            if ($this->save()) {
                SessionUnlockedEvent::dispatch($this, Auth::user());
                return true;
            } else {
                Log::error(sprintf('Unable to unlock session %s with code %s', $this->uuid, $code));
                return false;
            }
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
        if (!$sessionId) {
            return null;
        }

        try {
            return self::findByUuid($sessionId);
        } catch (SessionNotFoundException $e) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);

            Log::warning(
                sprintf('Session %s not found: %s', $sessionId, $e->getMessage()),
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
