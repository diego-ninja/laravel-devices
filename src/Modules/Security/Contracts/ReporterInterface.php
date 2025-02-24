<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Enums\RiskLevel;

interface ReporterInterface
{
    public function setLevel(RiskLevel $level): void;

    public function report(string $name, Risk $risk): bool;

    /**
     * @param  Collection<string, Risk>  $risks  with risks names as collection keys
     */
    public function reportMany(Collection $risks): bool;
}
