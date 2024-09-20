<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
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

/**
 * Class Session
 *
 * @package Ninja\DeviceManager\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property integer                      $id                     unsigned int
 * @property integer                      $user_id                unsigned int
 * @property string                       $device_uid             string
 * @property string                       $ip                     string
 * @property array                        $location               json
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

    protected $table = 'device_sessions';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'device_uid',
        'ip',
        'location',
        'started_at',
        'last_activity_at'
    ];


    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'uid', 'device_uid');
    }

    public function user(): HasOne
    {
        return $this->hasOne(Authenticatable::class, 'id', 'user_id');
    }

    public function status(): string
    {
        if ($this->block) {
            return self::STATUS_BLOCKED;
        }

        if ($this->finished_at !== null) {
            return self::STATUS_FINISHED;
        }

        if (abs(strtotime($this->last_activity_at) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200)) {
            return self::STATUS_INACTIVE;
        }

        return self::STATUS_ACTIVE;
    }

    public static function start(): Session
    {
        $deviceId = Cookie::get('d_i');
        $userId = Auth::user()->id;
        $now = Carbon::now();
        /** @var Location $location */
        $location = app(LocationProvider::class)->location();

        if ($deviceId) {
            self::endPreviousSessions($deviceId, $userId, $now);
        }

        $session = self::create([
            'user_id' => $userId,
            'device_uid' => $deviceId,
            'ip' => request()->ip(),
            'location' => $location->json(),
            'started_at' => $now,
            'last_activity_at' => $now
        ]);

        SessionFacade::put(self::DEVICE_SESSION_ID, $session->id);

        return $session;
    }

    private static function endPreviousSessions($deviceId, $userId, $now): void
    {
        self::where('device_uid', $deviceId)
            ->where('user_id', $userId)
            ->whereNull('finished_at')
            ->update(['finished_at' => $now]);
    }

    public static function end(bool $forgetSession = false): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return false;
        }

        $session = self::getSession();
        if (!$session) {
            return false;
        }

        $session->finished_at = Carbon::now();
        $session->save();

        if ($forgetSession) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);
        }

        return true;
    }

    public static function renew(): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return false;
        }

        $session = self::getSession();

        if (!$session) {
            return false;
        }

        $session->last_activity_at = Carbon::now();
        $session->finished_at = null;
        $session->save();

        return true;
    }

    public static function restart(Request $request): bool
    {
        foreach (Config::get('devices.ignore_restart', []) as $ignore) {
            if (self::shouldIgnoreRestart($request, $ignore)) {
                return false;
            }
        }

        return self::renew();
    }

    private static function shouldIgnoreRestart(Request $request, array $ignore): bool
    {
        return ($request->route()->getName() === $ignore['route'] || $request->route()->getUri() === $ignore['route'])
            && $request->route()->methods()[0] == $ignore['method'];
    }
    public static function isInactive($user): bool
    {
        if ($user) {
            return self::isUserInactive($user);
        }

        return self::isCurrentSessionInactive();
    }

    private static function isUserInactive($user): bool
    {
        if ($user->sessions->count() > 0) {
            $lastActivity = $user->getFreshestSession()->last_activity;
            return $lastActivity && abs(strtotime($lastActivity) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200);
        }

        return true;
    }

    private static function isCurrentSessionInactive(): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return true;
        }

        $session = self::getSession();

        return
            $session?->last_activity_at &&
            abs(strtotime($session?->last_activity_at) - strtotime(now())) > Config::get('devices.inactivity_seconds', 1200);
    }

    public static function blockById($sessionId): bool
    {
        try {
            $session = self::findOrFail($sessionId);
        } catch (Exception $e) {
            return false;
        }

        $session->block();
        return true;
    }

    public function block(): void
    {
        $this->block = true;
        $this->blocked_by = Auth::user()->id;
        $this->save();
    }

    public static function isBlocked(): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return true;
        }

        $session = self::getSession();
        return $session->block;
    }

    public static function isLocked(): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return true;
        }

        $session = self::getSession();
        return $session->login_code !== null;
    }

    public static function loginCode(): ?string
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return null;
        }

        $session = self::getSession();
        return $session->login_code;
    }

    public static function lockByCode(): ?int
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return null;
        }

        $session = self::getSession();
        $code = rand(100000, 999999);
        $session->login_code = md5($code);

        $session->save();

        return $code;
    }

    public static function refreshCode(): ?int
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return null;
        }

        $session = self::getSession();
        $code = rand(100000, 999999);
        $session->login_code = md5($code);
        $session->started_at = Carbon::now();

        $session->save();

        return $code;
    }

    public static function unlockByCode($code): int
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return -1;
        }

        $session = self::getSession();
        if (time() - strtotime($session->started_at) > Config::get('devices.security_code_lifetime', 1200)) {
            return -2;
        }

        if (md5($code) === $session->login_code) {
            $session->login_code = null;
            $session->save();
            return 0;
        }

        return -1;
    }

    private static function getSession(): ?Session
    {
        try {
            return self::findOrFail(SessionFacade::get(self::DEVICE_SESSION_ID));
        } catch (Exception $e) {
            SessionFacade::forget(self::DEVICE_SESSION_ID);

            Log::warning(
                sprintf('Session %s not found: %s', SessionFacade::get(self::DEVICE_SESSION_ID), $e->getMessage())
            );

            return null;
        }
    }
}
