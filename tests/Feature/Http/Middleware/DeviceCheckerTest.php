<?php

namespace Ninja\DeviceTracker\Tests\Feature\Http\Middleware;

use Faker\Provider\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Factories\DeviceIdFactory;
use Ninja\DeviceTracker\Http\Middleware\DeviceChecker;
use Ninja\DeviceTracker\Http\Middleware\DeviceTracker;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeviceCheckerTest extends FeatureTestCase
{
    public static function unavailableDeviceDataProvider(): array
    {
        return [
            'exception' => [
                'middlewareException' => true
            ],
            '403' => [
                'middlewareException' => false
            ],
        ];
    }

    #[DataProvider('unavailableDeviceDataProvider')]
    public function testWithUnavailableDevice(bool $middlewareException): void {
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => $middlewareException
        ]);

        $request = request();
        $next = function (Request $request) {
            return new Response(null, 200);
        };

        $this->expectException($middlewareException ? DeviceNotFoundException::class : HttpException::class);

        $middleware = new DeviceChecker();
        $middleware->handle($request, $next);
    }

    public function testWithAvailableDevice(): void {
        $device = Device::factory()
            ->create();
        $request = DeviceTransport::propagate($device->uuid);

        $next = function (Request $request) {
            return new Response(null, 200);
        };
        $middleware = new DeviceChecker();

        /** @var Response $response */
        $response = $middleware->handle($request, $next);
        $this->assertTrue($response->isOk());
    }

    public function testCustomHttpErrorCode(): void {
        $code = 412;
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => false,
            'devices.middlewares.device-checker.http_error_code' => $code,
        ]);

        $request = request();
        $next = function (Request $request) {
            $this->fail("It should not have entered the next() function");
        };

        $middleware = new DeviceChecker();
        try {
            $middleware->handle($request, $next);
            $this->fail("It should generate an exception");
        } catch (HttpException $e) {
            $this->assertEquals($code, $e->getStatusCode());
        }
    }

    public function testCustomHttpErrorCodeWithInvalidCode(): void {
        $code = 10000;
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => false,
            'devices.middlewares.device-checker.http_error_code' => $code,
        ]);

        $request = request();
        $next = function (Request $request) {
            $this->fail("It should not have entered the next() function");
        };

        $middleware = new DeviceChecker();
        try {
            $middleware->handle($request, $next);
            $this->fail("It should generate an exception");
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
