<?php

namespace Ninja\DeviceTracker\Modules\Security\DTO;

use Illuminate\Support\Collection;
use JsonSerializable;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevelMode;

final readonly class Risk implements JsonSerializable
{
    private const float LOW = 0.2;

    private const float MEDIUM = 0.4;

    private const float HIGH = 0.7;

    private const float CRITICAL = 0.9;

    public RiskLevel $level;

    public float $score;

    /**
     * @param  Collection<RiskFactor>  $factors
     */
    public function __construct(
        public Collection $factors,
        public RiskLevelMode $riskLevelMode = RiskLevelMode::WeightedAverage,
    ) {
        $totalWeight = $this->factors->reduce(
            fn (float $carry, RiskFactor $factor) => $carry + $factor->weight,
            0,
        );
        $this->score = $this->factors->reduce(
            fn (float $carry, RiskFactor $factor) => match ($this->riskLevelMode) {
                RiskLevelMode::WeightedAverage => $carry + ($factor->score * $factor->weight) / $totalWeight,
                RiskLevelMode::Average => $carry + $factor->score / $this->factors->count(),
                RiskLevelMode::Min => min($carry, $factor->score),
                RiskLevelMode::Max => max($carry, $factor->score),
            },
            0,
        );

        if ($this->score >= self::CRITICAL) {
            $this->level = RiskLevel::Critical;
        } elseif ($this->score >= self::HIGH) {
            $this->level = RiskLevel::High;
        } elseif ($this->score >= self::MEDIUM) {
            $this->level = RiskLevel::Medium;
        } elseif ($this->score >= self::LOW) {
            $this->level = RiskLevel::Low;
        } else {
            $this->level = RiskLevel::None;
        }
    }

    /**
     * @param  Collection<RiskFactor>  $factors
     */
    public static function make(Collection $factors): self
    {
        return new self(
            factors: $factors,
        );
    }

    public function equals(Risk $risk): bool
    {
        return $this->score === $risk->score
            && $this->factors === $risk->factors;
    }

    public function score(): float
    {
        return $this->score;
    }

    public function level(): RiskLevel
    {
        return $this->level;
    }

    public function critical(): bool
    {
        return $this->level() === RiskLevel::Critical;
    }

    public function high(): bool
    {
        return $this->level() === RiskLevel::High || $this->critical();
    }

    public function medium(): bool
    {
        return $this->level() === RiskLevel::Medium || $this->high();
    }

    public function low(): bool
    {
        return $this->level() === RiskLevel::Low || $this->medium();
    }

    public function none(): bool
    {
        return $this->level() === RiskLevel::None;
    }

    public function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(
            factors: $data['factors'] ?? [],
        );
    }

    public function array(): array
    {
        return [
            'factors' => $this->factors,
        ];
    }

    public function json(): string
    {
        return json_encode($this->array());
    }

    public function jsonSerialize(): array
    {
        return $this->array();
    }
}
