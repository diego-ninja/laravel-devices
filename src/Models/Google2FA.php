<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;

/**
 * Class Google2FA
 *
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property int $id unsigned int
 * @property int $user_id unsigned int
 * @property bool $enabled boolean
 * @property string $secret string
 * @property ?Carbon $last_success_at datetime
 * @property Carbon $created_at datetime
 * @property Carbon $updated_at datetime
 */
class Google2FA extends Model
{
    protected $table = 'google_2fa';

    public function user(): HasOne
    {
        return $this->hasOne(Config::get('devices.authenticatable_class'), 'id', 'user_id');
    }

    public function enable(string $secret): bool
    {
        $this->secret = $secret;
        $this->enabled = true;
        $this->last_success_at = null;

        return $this->save();
    }

    public function disable(): bool
    {
        $this->enabled = false;
        $this->last_success_at = null;

        return $this->save();
    }

    public function success(): void
    {
        $this->last_success_at = Carbon::now();
        $this->save();
    }

    public function secret(): ?string
    {
        return $this->secret;
    }

    public function lastSuccess(): ?Carbon
    {
        return $this->last_success_at;
    }

    public function enabled(): bool
    {
        if (! Config::get('devices.google_2fa_enabled')) {
            return false;
        }

        return $this->enabled && $this->secret !== null;
    }
}
