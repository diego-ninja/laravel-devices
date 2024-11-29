<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ninja\DeviceTracker\Modules\Detection\Contracts\RequestTypeDetector;
use Ninja\DeviceTracker\Modules\Tracking\Cache\EventTypeCache;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class DetectorRegistry implements RequestTypeDetector
{
    private Collection $detectors;

    public function __construct()
    {
        $this->detectors = collect([
            new AuthenticationRequestDetector,
            new LivewireRequestDetector,
            new ApiRequestDetector,
            new AjaxRequestDetector,
            new RedirectResponseDetector,
            new PageViewDetector,
        ])->sortByDesc(fn ($detector) => $detector->priority());
    }

    public function priority(): int
    {
        return 0;
    }

    public function detect(Request $request, mixed $response): ?EventType
    {
        $cache = EventTypeCache::withRequest($request);

        return $cache->remember($cache->key(''), function () use ($request, $response) {
            return $this->detectors->first(function ($detector) use ($request, $response) {
                return $detector->supports($request, $response) ? $detector : null;
            })?->detect($request, $response);
        });
    }

    public function supports(Request $request, mixed $response): bool
    {
        return $this->detectors->contains(fn (RequestTypeDetector $detector) => $detector->supports($request, $response));
    }
}
