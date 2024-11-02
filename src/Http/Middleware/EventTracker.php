<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\EventType;
use Ninja\DeviceTracker\Models\Event;

use function session;

final class EventTracker
{
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);

        if ($this->redirect($response)) {
            Event::log(
                type: EventType::Redirect,
                session: session(),
                metadata: new Metadata([
                    "url" => request()->url(),
                    "user_agent" => request()->userAgent(),
                    "route" => request()->route()?->getName(),
                    "method" => request()->method(),
                ])
            );
        }

        if (!$this->html($response)) {
            Event::log(
                type: EventType::PageView,
                session: session(),
                metadata: new Metadata([
                    "url" => request()->url(),
                    "user_agent" => request()->userAgent(),
                    "route" => request()->route()?->getName(),
                    "method" => request()->method(),
                ])
            );
        }

        if ($this->json($response)) {
            Event::log(
                type: EventType::ApiRequest,
                session: session(),
                metadata: new Metadata([
                    "url" => request()->url(),
                    "user_agent" => request()->userAgent(),
                    "route" => request()->route()?->getName(),
                    "method" => request()->method(),
                ])
            );
        }

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

    private function redirect(Response $response): bool
    {
        return $response->isRedirection();
    }

    private function json(mixed $response): bool
    {
        if (!$response instanceof Response) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || !str_contains($contentType, 'application/json')) {
            return false;
        }

        return true;
    }
}
