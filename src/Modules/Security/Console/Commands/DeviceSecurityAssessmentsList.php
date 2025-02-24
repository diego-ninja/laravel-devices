<?php

namespace Ninja\DeviceTracker\Modules\Security\Console\Commands;

use Illuminate\Console\Command;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityAssessmentInterface;
use Ninja\DeviceTracker\Modules\Security\Contracts\SecurityManagerInterface;

final class DeviceSecurityAssessmentsList extends Command
{
    protected $signature = 'devices:security:assessments:list';

    protected $description = 'List all enabled security assessments';

    public function __construct(private readonly SecurityManagerInterface $manager)
    {
        parent::__construct();
    }
    

    public function handle(): void
    {
        $this->info('Currently enabled assessments:');

        $assessments = $this->manager->getAssessments();

        $this->table(
            ['id', 'name', 'description', 'class'],
            $assessments->map(
                fn (SecurityAssessmentInterface $assessment) => [
                    md5($assessment->name()),
                    $assessment->name(),
                    $assessment->description(),
                    get_class($assessment),
                ]
            )->toArray(),
        );
    }
}
