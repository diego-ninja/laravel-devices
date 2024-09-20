<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;

/**
 * Class DeviceManager
 *
 * @package Ninja\DeviceManager\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property string                       $id                     unsigned string
 * @property integer                      $user_id                unsigned string
 * @property string                       $uid                    string
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
        'user_id',
        'uid',
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
        return $this->hasMany(Session::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(Config::get("devices.authenticatable_class"), 'id', 'user_id');
    }

    public static function isUserDevice(): bool
    {
        if (Cookie::has('d_i')) {
            $user = Auth::user();
            if (in_array(Cookie::get('d_i'), $user->devicesUids())) {
                return true;
            }
        }
        return false;
    }

    public static function addUserDevice(?string $userAgent = null): bool
    {
        if (Cookie::has('d_i')) {
            $agent = new Agent(
                headers: request()->headers->all(),
                userAgent: $userAgent ?? request()->userAgent()
            );

            self::create([
                'user_id' => Auth::user()->id,
                'uid' => Cookie::get('d_i'),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'mobile' => $agent->isMobile(),
                'device' => $agent->device(),
                'device_type' => $agent->deviceType(),
                'robot' => $agent->isRobot(),
                'source' => $agent->getUserAgent()
            ]);

            return true;
        }

        return false;
    }
}
