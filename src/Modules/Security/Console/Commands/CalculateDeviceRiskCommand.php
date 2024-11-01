<?php

namespace Ninja\DeviceTracker\Modules\Security\Console\Commands;

use Illuminate\Console\Command;

class CalculateDeviceRiskCommand extends Command
{
    protected $signature = 'devices:security:risk
        {--device= : Specific device UUID to recalculate}
        {--older-than=60 : Recalculate for devices not updated in X minutes}
        {--force : Force recalculation regardless of last update time}
        {--batch-size=100 : Size of the batch for processing}';

    protected $description = 'Calculate risk score and level for devices';

    public function handle(): void
    {

    }
}
