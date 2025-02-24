<?php

namespace Ninja\DeviceTracker\Modules\Security\Contracts;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

interface SecurityManagerInterface
{
    /**
     * @param  Collection<SecurityAssessmentInterface>  $assessments
     */
    public function addSecurityAssessments(Collection $assessments): void;

    public function addSecurityAssessment(SecurityAssessmentInterface $assessment): void;

    /**
     * @return Collection<SecurityAssessmentInterface>
     */
    public function getAssessments(): Collection;

    public function evaluateAssessment(SecurityAssessmentInterface $assessment): Risk;

    public function runAllEvaluations(): void;
}
