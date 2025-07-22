<?php

namespace Ninja\DeviceTracker\Modules\Detection\Device;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractParser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\Cache\UserAgentCache;
use Ninja\DeviceTracker\DTO\Device;
use Ninja\DeviceTracker\Modules\Detection\Contracts;
use Ninja\DeviceTracker\Modules\Detection\DTO\Browser;
use Ninja\DeviceTracker\Modules\Detection\DTO\DeviceType;
use Ninja\DeviceTracker\Modules\Detection\DTO\Platform;

final class UserAgentDeviceDetector implements Contracts\DeviceDetector
{
    private DeviceDetector $dd;

    public function __construct()
    {
        AbstractDeviceParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_PATCH);
    }

    public function detect(Request|string $request): ?Device
    {
        $ua = is_string($request) ? $request : $request->header('User-Agent', $this->fakeUA());
        if (! is_string($ua) || empty($ua)) {
            return null;
        }

        $key = UserAgentCache::key($ua);

        $headers = $request instanceof Request ? $request->headers->all() : $_SERVER;
        $headers = collect($headers)
            ->map(fn (mixed $header) => is_array($header) && count($header) === 1 ? $header[0] : $header)
            ->toArray();
        $clientHints = ClientHints::factory($headers);

        $this->dd = new DeviceDetector(
            userAgent: $ua,
            clientHints: $clientHints,
        );

        $this->dd->parse();

        return UserAgentCache::remember($key, function () use ($clientHints) {
            return Device::from([
                'browser' => $this->browser(),
                'platform' => $this->platform($clientHints),
                'device' => $this->device(),
                'grade' => null,
                'source' => $this->dd->getUserAgent(),
                'bot' => $this->dd->isBot(),
            ]);
        });
    }

    private function browser(): Browser
    {
        return Browser::from(
            $this->dd->getClient()
        );
    }

    private function platform(?ClientHints $clientHints = null): Platform
    {
        $os = $this->dd->getOs();
        $os['version'] = $clientHints?->getOperatingSystemVersion() ?? $os['version'];

        return Platform::from($os);
    }

    private function device(): DeviceType
    {
        $clientHintsModel = $this->clientHints->getModel();
        return DeviceType::from([
            'family' => $this->dd->getBrandName(),
            'model' => ! empty($clientHintsModel) ? $clientHintsModel : $this->dd->getModel(),
            'type' => $this->dd->getDeviceName(),
        ]);
    }

    private function fakeUA(): ?string
    {
        if (app()->environment('local')) {
            $uas = config('devices.development_ua_pool');
            shuffle($uas);

            return $uas[0] ?? null;
        }

        return null;
    }
}
