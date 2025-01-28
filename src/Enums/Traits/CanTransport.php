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
    public function get(?string $parameter = null): ?StorableId
    {
        return match ($this) {
            self::Cookie => $this->fromCookie($parameter),
            self::Header => $this->fromHeader($parameter),
            self::Session => $this->fromSession($parameter),
            self::Request => $this->fromRequest($parameter),
        } ?? $this->fromRequest($parameter);
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

    protected static function storableIdFromHierarchy(
        array $hierarchy,
    ): ?StorableId {
        $parameter = self::parameter();
        $transports = [];
        foreach ($hierarchy as $item) {
            $transport = self::tryFrom($item);
            if (! is_null($transport)) {
                $transports[] = $transport;
                $storableId = $transport->get($parameter);
                if (! is_null($storableId)) {
                    return $storableId;
                }
            }
        }

        $alternativeParameter = self::alternativeParameter();
        foreach ($transports as $transport) {
            $storableId = $transport->get($alternativeParameter);
            if (! is_null($storableId)) {
                return $storableId;
            }
        }

        return null;
    }

    protected static function getResponseTransport(?array $hierarchy = null, self $default = self::Cookie): self
    {
        if (empty($hierarchy)) {
            $hierarchy = [];
        }
        $hierarchy = array_map(fn (string $transport) => self::tryFrom($transport), $hierarchy);
        $hierarchy = array_filter(
            $hierarchy,
            fn (?self $transport) => ! is_null($transport) && $transport !== self::Request->value
        );
        if (empty($hierarchy)) {
            $hierarchy = [$default];
        }

        return $hierarchy[0];
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

    private function fromCookie(?string $parameter = null): ?StorableId
    {
        $parameter ??= self::parameter();
        $value = Cookie::get($parameter);
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $id = null;
        try {
            $id = self::storableIdFactory()::from($value);
        } catch (\Throwable) {
        }

        if (! $id instanceof StorableId) {
            try {
                $id = self::storableIdFactory()::from($this->decryptCookie($value));
            } catch (\Throwable) {
            }
        }

        return $id;
    }

    private function fromHeader(?string $parameter = null): ?StorableId
    {
        $parameter ??= self::parameter();
        $value = request()->header($parameter);
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return self::storableIdFactory()::from($value);
    }

    private function fromSession(?string $parameter = null): ?StorableId
    {
        $parameter ??= self::parameter();
        $value = Session::get($parameter);
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return self::storableIdFactory()::from($value);
    }

    private function fromRequest(?string $parameter = null): ?StorableId
    {
        $parameter ??= self::parameter();
        $value = request()->input($parameter);
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        return self::storableIdFactory()::from($value);
    }
}
