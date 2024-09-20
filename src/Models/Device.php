<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
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

//            $agent->setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3");

            self::create([
                'user_id' => Auth::user()->id,
                'uid' => Cookie::get('d_i'),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'mobile' => $agent->isMobile(),
                'device' => $agent->device(),
                'robot' => $agent->isRobot(),
                'source' => $agent->getUserAgent()
            ]);

            return true;
        }

        return false;
    }
}
