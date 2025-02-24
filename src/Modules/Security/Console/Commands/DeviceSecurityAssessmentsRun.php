<?php

namespace Ninja\DeviceTracker\Modules\Security\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityAssessmentInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityManagerInterface;
use Ninja\DeviceTracker\Modules\Security\DTO\RiskFactor;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputOption;

final class DeviceSecurityAssessmentsRun extends Command
{
    protected $signature = 'devices:security:assessments:run';

    protected $description = 'Run all enabled security assessments';

    public function __construct(private readonly SecurityManagerInterface $manager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: 'assessments',
                shortcut: 'a',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'The assessments ids to run in a comma-separated string (you can run devices:security:assessments:list to get the ids)',
            );
    }

    public function handle(): void
    {
        $this->info('Currently enabled assessments:');

        $assessments = $this->manager->getAssessments();

        $assessments = $this->filterAssessments($assessments);

        $this->table(
            headers: $this->getHeaders(),
            rows: $this->getRows($assessments),
            tableStyle: $this->getTableStyle(),
        );
    }

    /**
     * @param  Collection<SecurityAssessmentInterface>  $assessments
     * @return Collection<SecurityAssessmentInterface>
     */
    private function filterAssessments(Collection $assessments): Collection
    {
        $option = $this->option('assessments');
        if ($option !== null) {
            $ids = explode(',', $option);

            $assessments = $assessments->filter(fn (SecurityAssessmentInterface $assessment) => in_array(md5($assessment->name()), $ids));
        }

        return $assessments;
    }

    /**
     * @return string[]
     */
    private function getHeaders(): array
    {
        return ['name', 'danger level', 'danger %', 'description', 'factors'];
    }

    /**
     * @param  Collection<SecurityAssessmentInterface>  $assessments
     * @return array<string[]>
     */
    private function getRows(Collection $assessments): array
    {
        return $assessments->map(
            function (SecurityAssessmentInterface $assessment) {
                $risk = $this->manager->evaluateAssessment($assessment);

                return [
                    $assessment->name(),
                    $risk->level->name,
                    sprintf('%s%%', round($risk->score * 100, 2)),
                    $assessment->description(),
                    json_encode(
                        $risk->factors
                            ->filter(fn (RiskFactor $factor) => $factor->score > 0)
                            ->map(fn (RiskFactor $factor) => $factor->name)
                            ->toArray()
                    ),
                ];
            }
        )->toArray();
    }

    private function getTableStyle(): TableStyle
    {
        return Table::getStyleDefinition('box-double');
    }
}
