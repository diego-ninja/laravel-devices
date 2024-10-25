<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Tracking;

trait HasTrackingPoints
{
    public function tracking(): BelongsTo
    {
        return $this->belongsTo(Tracking::class)->with('points');
    }
}
