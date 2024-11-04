<?php

namespace Ninja\DeviceTracker\Modules\Security\Analyzer;

use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Security\DTO\Risk;

final class NavigationPatternAnalyzer extends AbstractAnalyzer
{
    private const MIN_NORMAL_PAGE_TIME = 3;
    private const MAX_NORMAL_PAGE_TIME = 3600;
    public function analyze(Device $device): Risk
    {
        // TODO: Implement analyze() method.
    }

    private function analyzeNavigationSpeed(Collection $events): float
    {
        $timeDiffs = $this->calculateTimeDiffs($events);
        if (empty($timeDiffs)) return 0.0;

        $anomalies = array_filter($timeDiffs, function($diff) {
            return $diff < self::MIN_NORMAL_PAGE_TIME ||
                $diff > self::MAX_NORMAL_PAGE_TIME;
        });

        return count($anomalies) / count($timeDiffs);
    }
    private function analyzePageSequence(Collection $events): float
    {
        $currentUrls = $events->pluck('metadata.url')->toArray();
        if (count($currentUrls) < 3) {
            return 0.0;
        }

        $historicalPatterns = $this->repository->getHistoricalPatterns(
            $events->first()->user_id,
            config('devices.behavior_analysis.windows.medium')
        );

        // Extraer secuencias históricas
        $historicalSequences = $this->extractSequences(
            collect($historicalPatterns)
                ->where('type', 'pageview')
                ->pluck('metadata.url')
                ->toArray(),
            3
        );

        if (empty($historicalSequences)) {
            return 0.0;
        }

        // Comparar la secuencia actual con las históricas
        $currentSequences = $this->extractSequences($currentUrls, 3);
        $matchCount = 0;
        $totalSequences = count($currentSequences);

        foreach ($currentSequences as $sequence) {
            if (isset($historicalSequences[$sequence])) {
                $matchCount++;
            }
        }

        $similarityThreshold = config('devices.behavior_analysis.analyzers.navigation.thresholds.sequence_similarity');
        $similarity = $totalSequences > 0 ? $matchCount / $totalSequences : 0;

        return max(0, 1 - ($similarity / $similarityThreshold));
    }
    private function extractSequences(array $items, int $length): array
    {
        $sequences = [];
        for ($i = 0; $i <= count($items) - $length; $i++) {
            $sequence = implode('>', array_slice($items, $i, $length));
            $sequences[$sequence] = ($sequences[$sequence] ?? 0) + 1;
        }
        return $sequences;
    }
}
