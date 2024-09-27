<?php

namespace Ninja\DeviceTracker;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\AbstractParser;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use hisorange\BrowserDetect\Contracts\ResultInterface;
use hisorange\BrowserDetect\Parser;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Browser;
use Ninja\DeviceTracker\DTO\Device;
use Ninja\DeviceTracker\DTO\DeviceType;
use Ninja\DeviceTracker\DTO\Platform;
use Ninja\DeviceTracker\DTO\Version;

final class UserAgentDeviceDetector implements Contracts\DeviceDetector
{
    private DeviceDetector $dd;
    private ?ResultInterface $parser = null;
    private Request $request;

    public function __construct()
    {
        AbstractDeviceParser::setVersionTruncation(AbstractParser::VERSION_TRUNCATION_PATCH);
    }

    public function detect(Request $request): Device
    {
        $this->request = $request;

        $this->dd = new DeviceDetector(
            userAgent: $request->header('User-Agent', ''),
            clientHints: ClientHints::factory($_SERVER)
        );

        $this->dd->parse();

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


    private function parser(): ResultInterface
    {
        if (!$this->parser) {
            $this->parser = (new Parser(request: $this->request))->detect();
        }

        return $this->parser;
    }
}
