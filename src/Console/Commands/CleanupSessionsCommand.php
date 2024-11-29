<?php

namespace Ninja\DeviceTracker\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Session;

final class CleanupSessionsCommand extends Command
{
    protected $signature = 'devices:sessions:cleanup {--days=30 : Days to keep finished sessions}';

    protected $description = 'Clean up old and inactive sessions';

    public function handle(): void
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        // Delete old finished sessions
        $deletedFinished = Session::where('status', SessionStatus::Finished)
            ->where('finished_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deletedFinished} old finished sessions.");

        // Handle inactive sessions based on config
        $inactivitySeconds = config('devices.inactivity_seconds', 1200);
        if ($inactivitySeconds > 0) {
            $cutoffTime = Carbon::now()->subSeconds($inactivitySeconds);

            /** @var Collection<Session> $inactiveSessions */
            $inactiveSessions = Session::where('status', SessionStatus::Active)
                ->where('last_activity_at', '<', $cutoffTime)
                ->get();

            foreach ($inactiveSessions as $session) {
                if (config('devices.inactivity_session_behaviour') === 'terminate') {
                    $session->end();
                } else {
                    $session->status = SessionStatus::Inactive;
                    $session->save();
                }
            }

            $this->info("Processed {$inactiveSessions->count()} inactive sessions.");
        }
    }
}
