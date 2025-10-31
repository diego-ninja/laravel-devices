<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Ninja\DeviceTracker\Models\ChangeHistory;

final class ClearHistoryCommand extends Command
{
    protected $signature = 'devices:history:clear
        {--m|model= : Model history to clear (device or session)}
        {--a|min-age=30 : Clear history older than the specified days }';

    protected $description = 'Clear old rows from the history table for a specific model';

    public function handle(): void
    {
        $this->info('Clearing history...');
        $model = $this->option('model') ?? null;
        $age = $this->option('min-age') ?? null;
        $age = $age === null ? 30 : intval($age);

        $query = ChangeHistory::query()
            ->where('created_at', '<', Carbon::now()->subDays($age));

        if ($model !== null) {
            $query->where('model_type', '=', $model);
        }

        $query->delete();
        $this->info('History cleared successfully');
    }
}
