<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Ninja\DeviceTracker\Exception\FingerprintDuplicatedException;
use Ninja\DeviceTracker\Facades\DeviceManager;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Enums\Library;
use Ninja\DeviceTracker\Modules\Fingerprinting\Injector\Factories\InjectorFactory;

final class FingerprintTracker
{
    /**
     * @throws FingerprintDuplicatedException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! config('devices.fingerprinting_enabled')) {
            return $next($request);
        }

        $response = $next($request);

        if (! DeviceManager::fingerprinted()) {
            if ($this->redirect($response)) {
                return $response;
            }

            if (! $this->html($response)) {
                return $response;
            }

            return $this->addFingerprint($response);
        }

        return $response;
    }

    /**
     * @throws FingerprintDuplicatedException
     */
    private function addFingerprint(Response $response): Response
    {
        $clientCookie = Config::get('devices.client_fingerprint_key');
        $serverCookie = Config::get('devices.fingerprint_id_cookie_name');

        $library = Config::get('devices.fingerprint_client_library', Library::FingerprintJS);

        if (! request()->cookie($clientCookie)) {
            return InjectorFactory::make($library)->inject($response);
        } else {
            $fingerprint = request()->cookie($clientCookie);
            if (! is_string($fingerprint)) {
                return $response;
            }

            device()?->fingerprint($fingerprint, $serverCookie);
            Cookie::queue(Cookie::forget($clientCookie));
        }

        return $response;
    }

    private function html(mixed $response): bool
    {
        if (! $response instanceof Response) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type');
        if (! $contentType || ! str_contains($contentType, 'text/html')) {
            return false;
        }

        return true;
    }

    private function redirect(mixed $response): bool
    {
        return $response instanceof RedirectResponse;
    }
}
