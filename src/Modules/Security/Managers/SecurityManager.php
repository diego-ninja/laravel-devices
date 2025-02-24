<?php

namespace Ninja\DeviceTracker\Modules\Security\Managers;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Contracts\ReporterInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityAssessmentInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityManagerInterface;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;
use Ninja\DeviceTracker\Modules\Security\Factories\ReporterFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * TODO
 *   1. Run on crontab - Configure the command in the bootstrap/app
 *   DONE 2. Load rules or rules collections from providers
 *   DONE 3. Run on demand (command)
 *   4. Run on event
 *   DONE 5. Use configured reporters to report risks
 *   DONE 6. Add report level that filters report by risk level
 *   DONE 7. Add filters to assessments:run command
 */
final class SecurityManager implements SecurityManagerInterface
{
    /** @var Collection<SecurityAssessmentInterface> */
    private Collection $assessments;

    /** @var Collection<ReporterInterface> */
    private Collection $reporters;

    public function __construct(private readonly LoggerInterface $logger)
    {
        $this->assessments = collect();
        $this->reporters = collect();

        $this->initializeReporters();
    }

    public function addSecurityAssessments(Collection $assessments): void
    {
        $this->assessments = $this->assessments->push(...$assessments);
    }

    public function addSecurityAssessment(SecurityAssessmentInterface $assessment): void
    {
        $this->assessments->push($assessment);
    }

    public function runAllEvaluations(): void
    {
        $this->assessments->each(fn (SecurityAssessmentInterface $assessment) => $this->evaluateAssessment($assessment));
    }

    public function evaluateAssessment(SecurityAssessmentInterface $assessment): Risk
    {
        $risk = $assessment->evaluate();

        $this->reportRisk($assessment->name(), $risk);

        return $risk;
    }

    private function reportRisk(string $assessmentName, Risk $risk): void
    {
        foreach ($this->reporters as $reporter) {
            try {
                $reported = $reporter->report($assessmentName, $risk);
            } catch (Throwable $exception) {
                $this->logger->warning(sprintf('%s exception caught: %s', get_class($exception), $exception->getMessage()));
                $this->logger->warning($exception->getTraceAsString());
                $reported = false;
            }

            if (! $reported) {
                $this->logger->warning(sprintf(
                    'Could not report %s risk (score: %s) - factors %s',
                    $assessmentName,
                    $risk->level->name,
                    json_encode($risk->factors),
                ));
            }
        }
    }

    private function initializeReporters(): void
    {
        if ($this->reporters->isNotEmpty()) {
            return;
        }

        $reportersConfig = config('devices.modules.security.reporters', []);

        foreach ($reportersConfig as $reporterConfig) {
            try {
                $reporter = ReporterFactory::make($reporterConfig);
                if ($reporter instanceof ReporterInterface) {
                    $this->reporters->add($reporter);
                }
            } catch (Throwable $exception) {
                $this->logger->warning(sprintf(
                    '%s reporter could not be loaded: %s',
                    $reporterConfig['type'] ?? 'Unknown',
                    $exception->getMessage()
                ));
            }
        }
    }

    public function getAssessments(): Collection
    {
        return $this->assessments;
    }
}
