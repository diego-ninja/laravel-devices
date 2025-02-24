<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

interface SecurityAssessmentInterface
{
    public function evaluate(): Risk;
    public function name(): string;
    public function description(): ?string;
}
