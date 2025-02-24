<?php

namespace Ninja\DeviceTracker\Modules\Security\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\Modules\Security\Exceptions\InvalidSecurityFactorScoreException;

final readonly class RiskFactor implements JsonSerializable
{
    /**
     * @throws InvalidSecurityFactorScoreException
     */
    public function __construct(
        public string $name,
        public float $score,
        public float $weight = 1,
    ) {
        if ($this->score < 0 || $this->score > 1) {
            throw new InvalidSecurityFactorScoreException('Factor score should always be between 0 and 1');
        }
    }

    /**
     * @throws InvalidSecurityFactorScoreException
     */
    public static function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(
            name: $data['name'],
            score: $data['score'],
            weight: $data['weight'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'score' => $this->score,
            'weight' => $this->weight,
        ];
    }
}
