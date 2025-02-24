<?php

namespace Ninja\DeviceTracker\Modules\Security\Factories;

use Ninja\DeviceTracker\Modules\Security\Contracts\ReporterInterface;
use Ninja\DeviceTracker\Modules\Security\Enums\ReporterType;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;
use Ninja\DeviceTracker\Modules\Security\Reporter\LogReporter;
use Psr\Log\LoggerInterface;

final class ReporterFactory
{
    public static function make(array $config): ?ReporterInterface
    {
        $reporter = match ($config['type']) {
            ReporterType::Log->value => new LogReporter(app()->make(LoggerInterface::class)),
            default => app()->make($config['type']),
        };

        if ($reporter instanceof ReporterInterface) {
            $level = array_filter(
                RiskLevel::cases(),
                fn (RiskLevel $value) => strtolower($config['level']) === strtolower($value->name)
            )[0] ?? RiskLevel::Low;
            $reporter->setLevel($level);
        }

        return $reporter;
    }
}
