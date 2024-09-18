<?php

namespace Ninja\DeviceTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;

class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'user_id',
        'uid',
        'browser',
        'platform',
        'device'
    ];

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

    public static function addUserDevice(): bool
    {
        $agent = new Agent();
        if (Cookie::has('d_i')) {
             self::create([
                'user_id' => Auth::user()->id,
                'uid' => Cookie::get('d_i'),
                'browser' => $agent->browser(),
                'platform' => $agent->platform(),
                'device' => $agent->device()
             ]);
            return true;
        } else {
            return false;
        }
    }
}
