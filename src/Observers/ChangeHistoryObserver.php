<?php

namespace Ninja\DeviceTracker\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Ninja\DeviceTracker\Models\ChangeHistory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;

final class ChangeHistoryObserver
{
    public function updating(Model $model): void
    {
        if (config('devices.history.enabled', false)) {
            $attributes = config(sprintf('devices.history.models.%s', self::getModelKey($model)), []);
            foreach ($attributes as $attribute) {
                if (! $model->originalIsEquivalent($attribute)) {
                    $newHistory = new ChangeHistory([
                        'column' => $attribute,
                        'old_value' => $this->getStringValue($model->getOriginal($attribute)),
                        'new_value' => $this->getStringValue($model->getAttributeValue($attribute)),
                    ]);
                    $newHistory->model()->associate($model);
                    $newHistory->save();
                }
            }
        }
    }

    public function deleting(Model $model): void
    {
        if (config('history.enabled', false) && method_exists($model, 'history')) {
            /** @var MorphMany $history */
            $history = $model->history();
            $history->each(fn (ChangeHistory $changeHistory) => $changeHistory->delete());
        }
    }

    public static function getModelKey(Model $model): string
    {
        return match (get_class($model)) {
            Device::class => 'device',
            Session::class => 'session',
        };
    }

    private function getStringValue(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (is_scalar($value)) {
            return strval($value);
        }

        if (method_exists($value, '__toString')) {
            return $value->__toString();
        }

        return json_encode($value);
    }
}
