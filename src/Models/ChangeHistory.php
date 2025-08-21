<?php

namespace Ninja\DeviceTracker\Models;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Cache\DeviceCache;
use Ninja\DeviceTracker\Contracts\Cacheable;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Database\Factories\DeviceFactory;
use Ninja\DeviceTracker\DTO\Device as DeviceDTO;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Events\DeviceCreatedEvent;
use Ninja\DeviceTracker\Events\DeviceDeletedEvent;
use Ninja\DeviceTracker\Events\DeviceFingerprintedEvent;
use Ninja\DeviceTracker\Events\DeviceHijackedEvent;
use Ninja\DeviceTracker\Events\DeviceUpdatedEvent;
use Ninja\DeviceTracker\Events\DeviceVerifiedEvent;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Models\Relations\HasManySessions;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;
use Ninja\DeviceTracker\Modules\Tracking\Models\Relations\HasManyEvents;
use Ninja\DeviceTracker\Traits\PropertyProxy;
use PDOException;

/**
 * Class Device
 *
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin Builder<ChangeHistory>
 *
 * @property int $id unsigned int
 * @property string $column string
 * @property string $old_value string|null
 * @property string $new_value string|null
 * @property Carbon $created_at datetime|null
 */
class ChangeHistory extends Model
{
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'column',
        'old_value',
        'new_value',
    ];

    public function getTable(): string
    {
        return config('devices.history.table', 'laravel_devices_history');
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
