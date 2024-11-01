<?php

namespace Ninja\DeviceTracker;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractParser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\Cache\UserAgentCache;
use Ninja\DeviceTracker\DTO\Browser;
use Ninja\DeviceTracker\DTO\Device;
use Ninja\DeviceTracker\DTO\DeviceType;
use Ninja\DeviceTracker\DTO\Platform;
use Ninja\DeviceTracker\DTO\Version;

final readonly class UserAgentDeviceDetector implements Contracts\DeviceDetector
{
    private DeviceDetector $dd;

    public function __construct()
    {
        AbstractDeviceParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_PATCH);
    }

    public function detect(Request $request): ?Device
    {
        $ua = $request->header('User-Agent', $this->fakeUA());
        $key = UserAgentCache::key($ua);

        $this->dd = new DeviceDetector(
            userAgent: $request->header('User-Agent', $ua),
            clientHints: ClientHints::factory($_SERVER)
        );

        $this->dd->parse();

        if ($this->dd->isBot() && !config('devices.allow_bot_devices')) {
            return null;
        }

        return UserAgentCache::remember($key, function () use ($ua, $request) {
            return new Device(
                browser: $this->browser(),
                platform: $this->platform(),
                device: $this->device(),
                grade: null,
                userAgent: $this->dd->getUserAgent()
            );
        });
    }

    private function browser(): Browser
    {
        return new Browser(
            name: $this->dd->getClient('name'),
            version: Version::fromString($this->dd->getClient('version')),
            family: $this->dd->getClient('family'),
            engine: $this->dd->getClient('engine'),
            type: $this->dd->getClient('type')
        );
    }

    private function platform(): Platform
    {
        return new Platform(
            name: $this->dd->getOs('name'),
            version: Version::fromString($this->dd->getOs('version')),
            family: $this->dd->getOs('family')
        );
    }

    private function device(): DeviceType
    {
        return new DeviceType(
            family: $this->dd->getBrandName(),
            model: $this->dd->getModel(),
            type: $this->dd->getDeviceName()
        );
    }

    private function fakeUA(): string
    {
        if (app()->environment('local')) {
            $uas = config('devices.development_ua_pool');
            shuffle($uas);

            return $uas[0];
        }

        return '';
    }
}
