<?php

namespace Ninja\DeviceTracker\Modules\Security\Rules;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ninja\DeviceTracker\Modules\Security\DTO\RiskFactor;
use Ninja\DeviceTracker\Modules\Security\Exceptions\InvalidSecurityFactorScoreException;

readonly class SessionsWithSameIpAndUserAgentRule extends AbstractRule
{
    /**
     * @throws InvalidSecurityFactorScoreException
     */
    public function evaluate(): Collection
    {
        $results = DB::query()
            ->select(['devices.ip as ip', 'devices.source as user_agent', DB::raw('count(DISTINCT device_sessions.user_id) as users_count')])
            ->from('devices')
            ->join('device_sessions', 'devices.uuid', '=', 'device_uuid')
            ->where('last_activity_at', '>=', Carbon::now()->subSeconds($this->thresholds['seconds']))
            ->groupBy(['devices.source', 'devices.ip'])
            ->having(DB::raw('COUNT(DISTINCT device_sessions.user_id)'), '>=', $this->thresholds['count'])
            ->get();

        $factors = collect();

        if ($results->isNotEmpty()) {
            foreach ($results as $result) {
                $factors[] = new RiskFactor(
                    name: sprintf(
                        '("%s", "%s") found %s times in %s seconds',
                        $result->ip,
                        $result->user_agent,
                        $result->users_count,
                        $this->thresholds['seconds'],
                    ),
                    score: 1,
                    weight: $this->weight,
                );
            }
        }

        if ($factors->isEmpty()) {
            $factors->push(new RiskFactor(
                name: sprintf(
                    'No ip/user_agent found more than %s times in %s seconds',
                    $this->thresholds['count'] - 1,
                    $this->thresholds['seconds'],
                ),
                score: 0,
                weight: $this->weight
            ));
        }

        return $factors;
    }
}
