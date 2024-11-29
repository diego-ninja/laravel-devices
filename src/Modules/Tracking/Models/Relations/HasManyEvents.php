<?php

namespace Ninja\DeviceTracker\Modules\Tracking\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

class HasManyEvents extends HasMany
{
    public function type(EventType $type): HasMany
    {
        /** @var HasMany $relation */
        $relation = $this->where('type', $type);
        return $relation;

    }

    public function login(): HasMany
    {
        return $this->type(EventType::Login);
    }

    public function logout(): HasMany
    {
        return $this->type(EventType::Logout);
    }

    public function signup(): HasMany
    {
        return $this->type(EventType::Signup);
    }

    public function views(): HasMany
    {
        return $this->type(EventType::PageView);
    }

    public function last(int $count = 1): HasMany
    {
        /** @var HasMany $relation */
        $relation = $this->orderByDesc('occurred_at')->limit($count);
        return $relation;
    }
}
