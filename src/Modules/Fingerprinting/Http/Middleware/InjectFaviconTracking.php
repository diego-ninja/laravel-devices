<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FaviconTrackingService;

final readonly class InjectFaviconTracking
{
    public function __construct(private FaviconTrackingService $service)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if (!$this->html($response)) {
            return $response;
        }

        $content = $this->service->inject($response->getContent());
        $response->setContent($content);

        return $response;
    }

    private function html(mixed $response): bool
    {
        if (!$response instanceof Response) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || !str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }
}
