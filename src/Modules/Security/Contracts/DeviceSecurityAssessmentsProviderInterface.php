<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Illuminate\Support\Collection;

interface DeviceSecurityAssessmentsProviderInterface
{
    /**
     * @return Collection<SecurityAssessmentInterface>
     */
    public function getDeviceSecurityAssessments(): Collection;
}
