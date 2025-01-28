<?php

namespace Ninja\DeviceTracker\Enums\Traits;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Ninja\DeviceTracker\Contracts\StorableId;

trait CanTransport
{
    public function get(): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie(),
            self::Header => $this->fromHeader(),
            self::Session => $this->fromSession(),
            self::Request => $this->fromRequest(),
        } ?? $this->fromRequest();
    }

    public static function currentFromHierarchy(array $hierarchy, self $default): self
    {
        $defaultTransport = null;

        foreach ($hierarchy as $item) {
            $transport = self::tryFrom($item);
            if (! is_null($transport)) {
                if (is_null($defaultTransport)) {
                    $defaultTransport = $transport;
                }
                $storableId = $transport->get();
                if (! is_null($storableId)) {
                    return $transport;
                }
            }
        }

        return $defaultTransport ?? $default;
    }

    protected static function storableIdFromHierarchy(array $hierarchy): ?StorableId
    {
        foreach ($hierarchy as $item) {
            $transport = self::tryFrom($item);
            if (! is_null($transport)) {
                $storableId = $transport->get();
                if (! is_null($storableId)) {
                    return $storableId;
                }
            }
        }

        return null;
    }

    public static function set(mixed $response, StorableId $id): mixed
    {
        if (! self::isValidResponse($response)) {
            return $response;
        }

        $transport = self::responseTransport();

        $callable = match ($transport) {
            // Transport::Cookie and Transport::Request
            default => function () use ($response, $transport, $id): mixed {
                $response->withCookie(
                    Cookie::forever(
                        name: $transport->parameter(),
                        value: (string) $id,
                        secure: Config::get('session.secure', false),
                        httpOnly: Config::get('session.http_only', true)
                    )
                );

                return $response;
            },
            self::Header => function () use ($response, $transport, $id): mixed {
                $response->header($transport->parameter(), (string) $id);

                return $response;
            },
            self::Session => function () use ($response, $transport, $id): mixed {
                Session::put($transport->parameter(), (string) $id);

                return $response;
            },
        };

        return $callable();
    }

    public static function propagate(?StorableId $id = null): Request
    {
        $current = self::current();

        $transportId = $id ?? $current->get();
        if ($transportId === null) {
            return request();
        }

        $requestParameter = self::parameter();

        return request()->merge([$requestParameter => (string) $transportId]);
    }

    private static function isValidResponse(mixed $response): bool
    {
        $valid = [
            Response::class,
            JsonResponse::class,
        ];

        return in_array(get_class($response), $valid, true);
    }

    private function decryptCookie(string $cookieValue): string
    {
        $decryptedString = Crypt::decrypt($cookieValue, false);
        return CookieValuePrefix::remove($decryptedString);
    }
}
