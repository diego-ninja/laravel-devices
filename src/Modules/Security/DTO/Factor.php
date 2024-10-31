<?php

namespace Ninja\DeviceTracker\Modules\Security\DTO;

use JsonSerializable;

final class Factor implements JsonSerializable
{
    public function __construct(
        public string $name,
        public float $score
    ) {
    }

    public static function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        
        return new self(
            $data['name'],
            $data['score']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'score' => $this->score,
        ];
    }
}