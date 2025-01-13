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

        $this->dd = new DeviceDetector(
            userAgent: $ua,
            clientHints: ClientHints::factory($_SERVER)
        );

        $this->dd->parse();

        return UserAgentCache::remember($key, function () {
            return Device::from([
                'browser' => $this->browser(),
                'platform' => $this->platform(),
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

    private function platform(): Platform
    {
        return Platform::from(
            $this->dd->getOs()
        );
    }

    private function device(): DeviceType
    {
        return DeviceType::from([
            'family' => $this->dd->getBrandName(),
            'model' => $this->dd->getModel(),
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
