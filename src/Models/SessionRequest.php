<?php

namespace Ninja\DeviceTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRequest extends Model
{
    protected $table = 'device_session_requests';

    protected $fillable = [
        'session_id',
        'route',
        'uri',
        'name',
        'method',
        'parameters',
        'type'
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
