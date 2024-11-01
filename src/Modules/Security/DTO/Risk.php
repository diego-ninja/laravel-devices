<?php

namespace Ninja\DeviceTracker\Modules\Security\DTO;

use JsonSerializable;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;

final class Risk implements JsonSerializable
{
    public const CRITICAL = 0.9;
    public const HIGH = 0.7;
    public const MEDIUM = 0.5;

    public function __construct(
        public RiskLevel $level,
        public int $score,
        public array $factors = []
    ) {
    }

    public static function default(): self
    {
        return new self(
            level: RiskLevel::Low,
            score: 0,
            factors: []
        );
    }

    public function changed(Risk $risk): bool
    {
        return abs($this->score - $risk->score) > config('devices.security.risk.significant_change_threshold', 0.2);
    }

    public function factor(Factor $factor): void
    {
        $this->factors[$factor->name] = $factor;
        $this->score += $factor->score;

        $this->calculate();
    }

    public function critical(): bool
    {
        return $this->level === RiskLevel::Critical;
    }

    public function high(): bool
    {
        return $this->level === RiskLevel::High || $this->critical();
    }

    public function medium(): bool
    {
        return $this->level === RiskLevel::Medium;
    }

    public function low(): bool
    {
        return $this->level === RiskLevel::Low;
    }

    private function calculate(): void
    {
        match (true) {
            $this->score >= self::CRITICAL => $this->level = RiskLevel::Critical,
            $this->score >= self::HIGH => $this->level = RiskLevel::High,
            $this->score >= self::MEDIUM => $this->level = RiskLevel::Medium,
            default => $this->level = RiskLevel::Low,
        };
    }

    public function from(string|array $data): self
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return new self(
            RiskLevel::from($data['level']),
            $data['score']
        );
    }

    public function array(): array
    {
        return [
            'level' => $this->level->value,
            'score' => $this->score,
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
