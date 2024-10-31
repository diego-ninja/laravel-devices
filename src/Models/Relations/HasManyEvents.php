<?php

namespace Ninja\DeviceTracker\Models\Relations;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Ninja\DeviceTracker\Enums\EventType;

class HasManyEvents extends HasMany
{
    public function type(EventType $type): HasMany
    {
        return $this->where('type', $type);
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
        return $this->type(EventType::Singup);
    }

    public function views(): HasMany
    {
        return $this->type(EventType::PageView);
    }

    public function last(int $count = 1): HasMany
    {
        return $this->orderByDesc('occurred_at')->limit($count);
    }
}
