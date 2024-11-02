<?php

namespace Ninja\DeviceTracker\Modules\Detection\Request;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;

final class AuthenticationRequestDetector extends AbstractRequestDetector
{
    protected const PRIORITY = 100;

    private array $signatures = [
        EventType::Login->value => [
            'paths' => ['login', 'auth/login'],
            'methods' => ['POST'],
        ],
        EventType::Logout->value => [
            'paths' => ['logout', 'auth/logout'],
            'methods' => ['POST', 'GET'],
        ],
        EventType::Signup->value => [
            'paths' => ['register', 'auth/register', 'signup'],
            'methods' => ['POST'],
        ],
    ];

    public function supports(Request $request, $response): bool
    {
        $path = trim($request->path(), '/');
        $method = strtoupper($request->method());

        foreach ($this->signatures as $signature) {
            if (in_array($method, $signature['methods'])) {
                foreach ($signature['paths'] as $signaturePath) {
                    if (fnmatch($signaturePath, $path)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function detect(Request $request, $response): ?EventType
    {
        $path = trim($request->path(), '/');

        foreach ($this->signatures as $event => $signature) {
            if ($this->matches($path, $request->method(), $signature)) {
                return EventType::from($event);
            }
        }

        return null;
    }

    private function matches(string $path, string $method, array $signature): bool
    {
        return in_array(strtoupper($method), $signature['methods']) &&
            collect($signature['paths'])->contains(fn($p) => fnmatch($p, $path));
    }
}