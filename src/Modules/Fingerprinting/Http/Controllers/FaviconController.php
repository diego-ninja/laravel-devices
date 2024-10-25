<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Http\Controllers;

use Cache;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Modules\Fingerprinting\Models\Tracking;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FaviconTrackingService;

class FaviconController
{
    public function __construct(private readonly FaviconTrackingService $service)
    {
    }

    public function serve(string $path): Response
    {
        $tracking = Tracking::current();
        if (!$tracking) {
            return $this->notFound();
        }

        // Rate limiting
        $key = sprintf("favicon_rate:{%d}:{%s}", $tracking->id, $path);
        if (!Cache::add($key, 1, 60)) {
            return $this->notFound();
        }

        $favicon = $this->service->handle($path, $tracking);
        if (!$favicon) {
            return $this->notFound();
        }

        return response(base64_decode($favicon))
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Expires', now()->addYear()->toRfc7231String());
    }

    public function response(array $result): Response
    {

    }

    private function notFound(): Response
    {
        return response('', 404);
    }
}
