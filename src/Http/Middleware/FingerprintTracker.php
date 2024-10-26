<?php

namespace Ninja\DeviceTracker\Http\Middleware;

use Closure;
use Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums\Library;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Factories\InjectorFactory;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\FingerprintJSInjector;

final class FingerprintTracker
{
    public function handle(Request $request, Closure $next)
    {
        if (!config("devices.fingerprinting_enabled")) {
            return $next($request);
        }

        $response = $next($request);

        if (!DeviceManager::fingerprinted()) {
            if ($this->redirect($response)) {
                return $response;
            }

            if (!$this->html($response)) {
                return $response;
            }

            return $this->addFingerprint($response);
        }

        return $response;
    }

    private function addFingerprint(Response $response): Response
    {
        $clientCookie = Config::get('devices.client_fingerprint_key');
        $serverCookie = Config::get('devices.fingerprint_id_cookie_name');

        $library = Config::get('devices.fingerprint_client_library', Library::FingerprintJS);

        if (!isset($_COOKIE[$clientCookie])) {
            return (InjectorFactory::make($library))->inject($response);
        } else {
            DeviceManager::current()?->fingerprint($_COOKIE[$clientCookie], $serverCookie);
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

    private function redirect(mixed $response): bool
    {
        return $response instanceof RedirectResponse;
    }
}
