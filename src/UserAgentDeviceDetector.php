<?php

namespace Ninja\DeviceTracker;

use hisorange\BrowserDetect\Contracts\ResultInterface;
use hisorange\BrowserDetect\Parser;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Browser;
use Ninja\DeviceTracker\DTO\Device;
use Ninja\DeviceTracker\DTO\DeviceType;
use Ninja\DeviceTracker\DTO\Platform;

final class UserAgentDeviceDetector implements Contracts\DeviceDetector
{
    private ?ResultInterface $parser = null;

    private Request $request;

    public function detect(Request $request): Device
    {
        $this->request = $request;

        return new Device(
            browser: $this->browser(),
            platform: $this->platform(),
            device: $this->device(),
            ip: $request->ip(),
            grade: $this->parser()->mobileGrade() === '' ? null : $this->parser()->mobileGrade(),
            userAgent: $request->header('User-Agent')
        );
    }

    private function browser(): Browser
    {
        return new Browser(
            name: $this->parser()->browserName(),
            version: $this->parser()->browserVersion(),
            family: $this->parser()->browserFamily(),
            engine: $this->parser()->browserEngine()
        );
    }

    private function platform(): Platform
    {
        return new Platform(
            name: $this->parser()->platformName(),
            version: $this->parser()->platformVersion(),
            family: $this->parser()->platformFamily()
        );
    }

    private function device(): DeviceType
    {
        return new DeviceType(
            family: $this->parser()->deviceFamily(),
            model: $this->parser()->deviceModel(),
            type: $this->parser()->deviceType()
        );
    }


    private function parser(): ResultInterface
    {
        if (!$this->parser) {
            $this->parser = (new Parser(request: $this->request))->detect();
        }

        return $this->parser;
    }
}
