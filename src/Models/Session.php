<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session as SessionFacade;
use Jenssegers\Agent\Agent;

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
 * @property string                       $location               string
 * @property boolean                      $block                  boolean
 * @property integer                      $blocked_by             unsigned int
 * @property string                       $login_code             string
 * @property Carbon                       $finished_at            datetime
 * @property Carbon                       $last_activity_at       datetime
 * @property Carbon                       $created_at             datetime
 * @property Carbon                       $updated_at             datetime
 *
 * @property Session                       $session
 */
class Session extends Model
{
    public const  DEVICE_SESSION_ID = 'session.id';

    protected $table = 'device_sessions';

    protected $fillable = [
        'user_id',
        'device_uid',
        'ip',
        'location',
        'last_activity_at'
    ];

    public const STATUS_DEFAULT = null;
    public const STATUS_BLOCKED = 1;

    public function device(): HasOne
    {
        return $this->hasOne(Device::class, 'device_uid', 'device_uid');
    }

    public static function start(): Session
    {
        $deviceId = Cookie::get('d_i');
        $userId = Auth::user()->id;
        $now = Carbon::now();

        if ($deviceId) {
            self::endPreviousSessions($deviceId, $userId, $now);
        }

        $session = self::create([
            'user_id' => $userId,
            'device_uid' => $deviceId,
            'ip' => request()->ip(),
            'last_activity' => $now
        ]);

        SessionFacade::put(self::DEVICE_SESSION_ID, $session->id);

        return $session;
    }

    private static function endPreviousSessions($deviceId, $userId, $now): void
    {
        self::where('device_uid', $deviceId)
            ->where('user_id', $userId)
            ->whereNull('end_date')
            ->update(['end_date' => $now]);
    }

    public static function end(bool $forgetSession = false): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return false;
        }

        $session = self::getSession();
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
            abs(strtotime($session?->last_activity_at) - strtotime(now())) > 1200;
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
        $this->block = self::STATUS_BLOCKED;
        $this->blocked_by = Auth::user()->id;
        $this->save();
    }

    public static function isBlocked(): bool
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return true;
        }

        $session = self::getSession();
        return $session->block == self::STATUS_BLOCKED;
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
        $session->created_at = Carbon::now();

        $session->save();

        return $code;
    }

    public static function unlockByCode($code): int
    {
        if (!SessionFacade::has(self::DEVICE_SESSION_ID)) {
            return -1;
        }

        $session = self::getSession();
        if (time() - strtotime($session->created_at) > Config::get('devices.security_code_lifetime', 1200)) {
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
