<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Ninja\DeviceTracker\Models\Device;

/**
 * @extends BelongsToMany<Device, User>
 */
final class BelongsToManyDevices extends BelongsToMany
{
    public function current(): ?Device
    {
        /** @var Device|null $device */
        $device = $this->get()->where('uuid', device_uuid())->first();

        return $device;
    }

    /**
     * @return array<string>
     */
    public function uuids(): array
    {
        return $this->get()->pluck('uuid')->toArray();
    }

    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        // We need to join to the intermediate table on the related model's primary
        // key column with the intermediate table's foreign key for the related
        // model instance. Then we can set the "where" for the parent models.
        $query->join(
            DB::raw(sprintf(
                '(SELECT device_uuid, user_id FROM %s GROUP BY device_uuid, user_id) as %s',
                $this->table,
                $this->table,
            )),
            $this->getQualifiedRelatedKeyName(),
            '=',
            $this->getQualifiedRelatedPivotKeyName()
        );

        return $this;
    }
}
