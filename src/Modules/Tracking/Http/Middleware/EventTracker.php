<?php

namespace Ninja\DeviceTracker\Modules\Tracking\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Facades\SessionManager;
use Ninja\DeviceTracker\Modules\Detection\Request\DetectorRegistry;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;
use Ninja\DeviceTracker\Modules\Tracking\Models\Event;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class EventTracker
{
    public function __construct(
        private DetectorRegistry $registry
    ) {}

    public function handle(Request $request, \Closure $next)
    {
        if (config('devices.event_tracking_enabled') === false) {
            return $next($request);
        }

        if ($this->ignore($request)) {
            return $next($request);
        }

        $response = $next($request);
        $type = $this->registry->detect($request, $response);

        if ($type === null) {
            return $response;
        }

        $this->log($type, $request, $response);

        return $response;
    }

    private function ignore(Request $request): bool
    {
        return collect([
            '_debugbar',
            '_ignition',
            'telescope',
            'horizon',
            'sanctum',
        ])->contains(fn ($path) => str_starts_with($request->path(), $path));
    }

    private function log(EventType $type, Request $request, mixed $response): Event
    {
        $metadata = new Metadata([
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->path(),
                'ajax' => $request->ajax(),
                'livewire' => $type->equals(EventType::LivewireUpdate),
                'referrer' => $request->header('referer'),
                'user_agent' => $request->userAgent(),
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'type' => $this->type($response),
            ],
            'route' => [
                'name' => $request->route()?->getName(),
                'action' => $request->route()?->getActionName(),
            ],
            'performance' => [
                'duration' => defined('LARAVEL_START') ?
                    (microtime(true) - LARAVEL_START) * 1000 : null,
            ],
            'client' => [
                'timezone' => $request->header('X-Timezone'),
                'language' => $request->getPreferredLanguage(),
                'screen' => $request->header('X-Screen-Size'),
            ],
            'security' => [
                'ip' => $request->ip(),
                'proxies' => $request->getClientIps(),
                'secure' => $request->secure(),
            ],
        ]);

        return Event::log(
            type: $type,
            session: SessionManager::current(),
            metadata: $metadata
        );
    }

    private function type(mixed $response): string
    {
        return match (true) {
            $response instanceof JsonResponse => 'json',
            $response instanceof RedirectResponse => 'redirect',
            $response instanceof BinaryFileResponse => 'file',
            $response instanceof Response => 'html',
            default => 'unknown'
        };
    }

    private function size(mixed $response): ?int
    {
        try {
            return strlen($response->getContent());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
