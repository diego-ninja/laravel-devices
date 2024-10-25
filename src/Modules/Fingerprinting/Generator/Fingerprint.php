<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Generator;

use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Tracking;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FingerprintingService;

final class Fingerprint
{
    private const TIME_WEIGHT = 0.4;     // Peso para factores temporales
    private const FREQ_WEIGHT = 0.3;     // Peso para factores de frecuencia
    private const PATTERN_WEIGHT = 0.3;  // Peso para el patrón básico

    public static function for(Tracking $tracking): string
    {
        return (new self())->generate($tracking);
    }

    public function generate(Tracking $tracking): string
    {
        return $this->combine([
            $this->pattern($tracking) => self::PATTERN_WEIGHT,
            $this->time($tracking) => self::TIME_WEIGHT,
            $this->frequency($tracking) => self::FREQ_WEIGHT
        ]);
    }

    public function rebuild(array $points): string
    {
        return $this->combine([
            $this->pattern($points) => self::PATTERN_WEIGHT,
            $this->time($points) => self::TIME_WEIGHT,
            $this->frequency($points) => self::FREQ_WEIGHT
        ]);
    }

    private function combine(array $components): string
    {
        $combined = '';
        foreach ($components as $component => $weight) {
            $bits = $this->weight($component, $weight);
            $combined = $this->xor($combined, $bits);
        }

        return $combined;
    }

    private function weight(string $bits, float $weight): string
    {
        $weightedBits = '';
        $weightInt = (int)($weight * 255);

        for ($i = 0; $i < strlen($bits); $i++) {
            $bit = (int)$bits[$i];
            $weightedBit = $bit & ($weightInt >> ($i % 8) & 1);
            $weightedBits .= $weightedBit;
        }

        return $weightedBits;
    }

    private function xor(string $str1, string $str2): string
    {
        $result = '';
        $len = max(strlen($str1), strlen($str2));
        $str1 = str_pad($str1, $len, '0', STR_PAD_LEFT);
        $str2 = str_pad($str2, $len, '0', STR_PAD_LEFT);

        for ($i = 0; $i < $len; $i++) {
            $result .= $str1[$i] ^ $str2[$i];
        }

        return $result;
    }

    private function pattern(Tracking|array $tracking): string
    {
        $points = is_array($tracking) ? $tracking : $tracking->points()
            ->wherePivot('count', '>', 0)
            ->pluck('index')
            ->toArray();

        $binary = array_fill(0, FingerprintingService::TRACKING_POINTS, '0');
        foreach ($points as $index) {
            $binary[$index] = '1';
        }

        return implode('', $binary);
    }


    private function time(Tracking $tracking): string
    {
        $timeData = $tracking->points()
            ->withPivot(["first_tracking_at", "last_tracking_at"])
            ->get()
            ->map(function ($point) {
                return [
                    "index" => $point->index,
                    "first" => $point->pivot->first_tracking_at,
                    "last" => $point->pivot->last_tracking_at,
                    "duration" => $point->pivot->first_tracking_at->diffInSeconds($point->pivot->last_tracking_at)
                ];
            });

        $signature = '';
        foreach ($timeData as $data) {
            $bits = str_pad(
                base_convert(
                    min(15, floor($data['duration'] / 86400)),
                    10,
                    2
                ),
                4,
                '0',
                STR_PAD_LEFT
            );
            $signature .= $bits;
        }

        return $signature;
    }

    private function frequency(Tracking $tracking): string
    {
        $frequencyData = $tracking->points()
            ->withPivot('count')
            ->get()
            ->map(function ($point) {
                return [
                    'index' => $point->index,
                    'count' => $point->pivot->count
                ];
            });

        $signature = '';
        foreach ($frequencyData as $data) {
            $bits = str_pad(
                base_convert(
                    min(7, $data['count']),
                    10,
                    2
                ),
                3,
                '0',
                STR_PAD_LEFT
            );
            $signature .= $bits;
        }

        return $signature;
    }
}
