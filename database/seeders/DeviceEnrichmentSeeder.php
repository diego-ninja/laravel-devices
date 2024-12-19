<?php

namespace Ninja\DeviceTracker\Database\Seeders;

use Carbon\Carbon;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

class DeviceEnrichmentSeeder extends Seeder
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

    public function run(): void
    {
        DB::table('devices')->orderBy('id')->chunk(100, function ($devices) {
            foreach ($devices as $device) {
                $riskLevel = $this->generateRiskLevel($device);

                DB::table('devices')
                    ->where('uuid', $device->uuid)
                    ->update([
                        'risk_score' => $riskLevel['score'],
                        'risk_level' => json_encode($riskLevel),
                        'risk_assessed_at' => now(),
                    ]);
            }
        });

        DB::table('device_events')->orderBy('id')->chunk(100, function ($events) {
            foreach ($events as $event) {
                $enrichedMetadata = $this->enrichEventMetadata($event);

                DB::table('device_events')
                    ->where('uuid', $event->uuid)
                    ->update([
                        'metadata' => json_encode($enrichedMetadata),
                    ]);
            }
        });
    }

    private function generateRiskLevel($device): array
    {
        $riskLevels = DataFactory::riskLevels();

        $baseScore = match ($device->status) {
            'verified' => $this->faker->numberBetween(0, 30),
            'hijacked' => $this->faker->numberBetween(71, 100),
            default => $this->faker->numberBetween(31, 70)
        };

        $riskFactors = [];

        $sessions = DB::table('device_sessions')
            ->where('device_uuid', $device->uuid)
            ->get();

        if ($sessions->count() > 0) {
            $uniqueLocations = $sessions->pluck('location')->unique()->count();
            if ($uniqueLocations > 3) {
                $riskFactors[] = 'multiple_locations';
                $baseScore += 10;
            }

            $unusualTimes = $sessions->filter(function ($session) {
                $hour = Carbon::parse($session->started_at)->format('H');

                return $hour >= 22 || $hour <= 5;
            })->count();

            if ($unusualTimes > 2) {
                $riskFactors[] = 'unusual_login_time';
                $baseScore += 5;
            }

            $this->detectImpossibleTravel($sessions, $riskFactors, $baseScore);
        }

        $level = match (true) {
            $baseScore <= 30 => 'low',
            $baseScore <= 70 => 'medium',
            default => 'high'
        };

        return [
            'score' => min(100, $baseScore),
            'level' => $level,
            'factors' => $riskFactors,
            'last_assessment' => now()->toIso8601String(),
        ];
    }

    private function detectImpossibleTravel(Collection $sessions, array &$riskFactors, int &$baseScore): void
    {
        $sessionPairs = $sessions->sortBy('started_at')->values();
        for ($i = 0; $i < $sessionPairs->count() - 1; $i++) {
            $current = json_decode($sessionPairs[$i]->location);
            $next = json_decode($sessionPairs[$i + 1]->location);

            if ($current && $next) {
                $distance = $this->calculateDistance(
                    $current->latitude,
                    $current->longitude,
                    $next->latitude,
                    $next->longitude
                );

                $timeDiff = Carbon::parse($sessionPairs[$i + 1]->started_at)
                    ->diffInHours(Carbon::parse($sessionPairs[$i]->started_at));

                // Velocidad promedio necesaria en km/h
                $requiredSpeed = $timeDiff > 0 ? $distance / $timeDiff : PHP_FLOAT_MAX;

                // Si la velocidad requerida es mayor a 800 km/h (velocidad promedio de avión comercial)
                // y el tiempo es menor a 3 horas, consideramos que es un viaje imposible
                if ($requiredSpeed > 800 && $timeDiff < 3) {
                    $riskFactors[] = 'impossible_travel';
                    $baseScore += 25; // Alto impacto en el score de riesgo
                    break;
                }
            }
        }
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $radius = 6371; // Radio de la Tierra en kilómetros

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radius * $c;
    }

    private function enrichEventMetadata($event): array
    {
        $metadata = json_decode($event->metadata, true) ?? [];
        $eventMetadata = DataFactory::eventMetadata();

        // Enriquecer según el tipo de evento
        switch ($event->type) {
            case EventType::Login->value:
                $metadata = array_merge($metadata, [
                    'auth' => [
                        'provider' => $this->faker->randomElement($eventMetadata['login']['auth_providers']),
                        'success' => $this->faker->boolean($eventMetadata['login']['success_rate'] * 100),
                        'failure_reason' => null,
                    ],
                    'security' => [
                        'geolocation' => [
                            'accuracy' => $this->faker->numberBetween(50, 100),
                            'confidence' => $this->faker->numberBetween(0, 100),
                        ],
                        'ip_type' => $this->determineIpType($event->ip_address),
                        'risk_indicators' => $this->generateRiskIndicators(),
                    ],
                ]);

                if (! $metadata['auth']['success']) {
                    $metadata['auth']['failure_reason'] = $this->faker->randomElement(
                        $eventMetadata['login']['failure_reasons']
                    );
                }
                break;

            case EventType::PageView->value:
                $section = $this->faker->randomElement(array_keys(DataFactory::commonPaths()));
                $path = $this->faker->randomElement(DataFactory::commonPaths()[$section]);

                $metadata = array_merge($metadata, [
                    'page' => [
                        'path' => $path,
                        'section' => $section,
                        'title' => ucwords(str_replace(['/', '-', '_'], ' ', $path)),
                        'referrer' => $this->faker->randomElement($eventMetadata['page_view']['referrers']),
                        'load_time' => $this->faker->numberBetween(
                            $eventMetadata['page_view']['load_time_range'][0],
                            $eventMetadata['page_view']['load_time_range'][1]
                        ),
                    ],
                    'client' => [
                        'viewport' => $this->faker->randomElement($eventMetadata['page_view']['viewports']),
                        'language' => $this->faker->randomElement(DataFactory::languages()),
                        'timezone' => $this->faker->randomElement(DataFactory::timezones()),
                        'dnt' => $this->faker->boolean(30),
                    ],
                ]);
                break;

            case EventType::Click->value:
                $metadata = array_merge($metadata, [
                    'interaction' => [
                        'element_type' => $this->faker->randomElement($eventMetadata['click']['element_types']),
                        'element_id' => 'btn_'.$this->faker->word,
                        'time_to_click' => $this->faker->numberBetween(
                            $eventMetadata['click']['interaction_time_range'][0],
                            $eventMetadata['click']['interaction_time_range'][1]
                        ),
                    ],
                ]);
                break;

            case EventType::ApiRequest->value:
                $metadata = array_merge($metadata, [
                    'api' => [
                        'method' => $this->faker->randomElement($eventMetadata['api_request']['methods']),
                        'endpoint' => '/api/v1/'.$this->faker->word,
                        'response_time' => $this->faker->numberBetween(
                            $eventMetadata['api_request']['response_time_range'][0],
                            $eventMetadata['api_request']['response_time_range'][1]
                        ),
                        'success' => $this->faker->boolean($eventMetadata['api_request']['success_rate'] * 100),
                        'status_code' => 200,
                    ],
                ]);

                if (! $metadata['api']['success']) {
                    $metadata['api']['status_code'] = $this->faker->randomElement([400, 401, 403, 404, 500]);
                }
                break;
        }

        return $metadata;
    }

    private function determineIpType(string $ip): string
    {
        $ipRanges = DataFactory::ipRanges();

        foreach ($ipRanges['suspicious'] as $range) {
            if ($this->ipInRange($ip, $range)) {
                return 'suspicious';
            }
        }

        foreach ($ipRanges['safe'] as $range) {
            if ($this->ipInRange($ip, $range)) {
                return 'safe';
            }
        }

        return 'unknown';
    }

    private function ipInRange(string $ip, string $range): bool
    {
        [$range, $netmask] = explode('/', $range);

        $ip_decimal = ip2long($ip);
        $range_decimal = ip2long($range);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;

        return ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal);
    }

    private function generateRiskIndicators(): array
    {
        $indicators = [];

        if ($this->faker->boolean(10)) {
            $indicators[] = 'unusual_browser_fingerprint';
        }

        if ($this->faker->boolean(5)) {
            $indicators[] = 'known_malicious_ip';
        }

        if ($this->faker->boolean(15)) {
            $indicators[] = 'automated_behavior';
        }

        return $indicators;
    }
}
