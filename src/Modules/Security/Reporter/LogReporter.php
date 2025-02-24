<?php

namespace Ninja\DeviceTracker\Modules\Security\Reporter;

use Illuminate\Support\Collection;
use Monolog\Level;
use Ninja\DeviceTracker\Modules\Security\Contracts\ReporterInterface;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;
use Psr\Log\LoggerInterface;

final class LogReporter extends AbstractReporter
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function report(string $name, Risk $risk): bool
    {
        if ($this->level->value > $risk->level->value) {
            return true;
        }

        $method = match ($risk->level) {
            RiskLevel::None => Level::Info,
            RiskLevel::Low => Level::Notice,
            RiskLevel::Medium => Level::Warning,
            RiskLevel::High => Level::Error,
            RiskLevel::Critical => Level::Critical,
        };
        $method = $method->toPsrLogLevel();

        $this->logger->log(
            $method,
            sprintf(
                '%s risk level detected (score: %s%%) for %s',
                $risk->level->name,
                round($risk->score * 100, 2),
                $name,
            ),
        );
        $this->logger->log(
            $method,
            sprintf(
                '  - factors: %s',
                json_encode($risk->factors),
            ),
        );

        return true;
    }

    public function reportMany(Collection $risks): bool
    {
        $result = true;
        foreach ($risks as $key => $risk) {
            $result = $this->report($key, $risk) && $result;
        }

        return $result;
    }
}
