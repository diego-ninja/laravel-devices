<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Services;

use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Point;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Tracking;
use Throwable;

final readonly class FaviconTrackingService
{
    private const TRACKING_POINTS = 32;
    private const BASE64_FAVICON = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    public function initialize(Device $device): Tracking
    {
        /** @var Tracking $tracking */
        $tracking = Tracking::create([
            'device_id' => $device->uuid,
            'storage_size' => self::TRACKING_POINTS
        ]);

        $tracking->initialize();

        return $tracking;
    }

    public function generate(string $identifier): array
    {
        $points = [];
        for ($i = 0; $i < self::TRACKING_POINTS; $i++) {
            $points[] = Point::create($this->path($identifier, $i), $i);
        }

        return $points;
    }

    public function handle(string $path, Tracking $tracking): ?array
    {
        $point = Point::byPath($path);
        if ($point === null) {
            return null;
        }

        if ($tracking->reading()) {
            $point->track($tracking);
            return null;
        }

        return $this->favicon($point, $tracking);
    }

    public function inject(string $html): string
    {
        $tracking = Tracking::current();
        if ($tracking === null) {
            return $html;
        }

        $script = $this->script($tracking->routes());

        return str_replace('</head>', $script . '</head>', $html);
    }

    /**
     * @throws Throwable
     */
    private function script(array $points): string
    {
        return sprintf(
            '<script>%s</script>',
            view('fingerprinting::tracking-script', [
                'tracking' => Tracking::current(),
                'routes' => $points
            ])->render()
        );
    }

    private function favicon(Point $point, Tracking $tracking): ?array
    {
        if (!$tracking->device->fingerprint) {
            return null;
        }

        return ($tracking->device->fingerprint >> $point->index) & 1 ?
            [
                "content" => base64_decode(self::BASE64_FAVICON),
                "cache" => true
            ]
            : null;
    }

    private function path(string $id, int $index): string
    {
        return hash('md5', sprintf("{%s}:{%d}", $id, $index));
    }
}
