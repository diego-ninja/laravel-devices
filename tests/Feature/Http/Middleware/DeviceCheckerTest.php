<?php

namespace Ninja\DeviceTracker\Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Exception\DeviceNotFoundException;
use Ninja\DeviceTracker\Http\Middleware\DeviceChecker;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use Ninja\DeviceTracker\Transports\DeviceTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeviceCheckerTest extends FeatureTestCase
{
    public static function unavailableDeviceDataProvider(): array
    {
        return [
            'exception' => [
                'middlewareException' => true,
            ],
            '403' => [
                'middlewareException' => false,
            ],
        ];
    }

    #[DataProvider('unavailableDeviceDataProvider')]
    public function test_with_unavailable_device(bool $middlewareException): void
    {
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => $middlewareException,
        ]);

        $request = request();
        $next = function (Request $request) {
            return new Response(null, 200);
        };

        $this->expectException($middlewareException ? DeviceNotFoundException::class : HttpException::class);

        $middleware = new DeviceChecker;
        $middleware->handle($request, $next);
    }

    public function test_with_available_device(): void
    {
        $device = Device::factory()
            ->create();
        $request = DeviceTransport::propagate($device->uuid);

        $next = function (Request $request) {
            return new Response(null, 200);
        };
        $middleware = new DeviceChecker;

        /** @var Response $response */
        $response = $middleware->handle($request, $next);
        $this->assertTrue($response->isOk());
    }

    public function test_custom_http_error_code(): void
    {
        $code = 412;
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => false,
            'devices.middlewares.device-checker.http_error_code' => $code,
        ]);

        $request = request();
        $next = function (Request $request) {
            $this->fail('It should not have entered the next() function');
        };

        $middleware = new DeviceChecker;
        try {
            $middleware->handle($request, $next);
            $this->fail('It should generate an exception');
        } catch (HttpException $e) {
            $this->assertEquals($code, $e->getStatusCode());
        }
    }

    public function test_custom_http_error_code_with_invalid_code(): void
    {
        $code = 10000;
        $this->setConfig([
            'devices.middlewares.device-checker.exception_on_unavailable_devices' => false,
            'devices.middlewares.device-checker.http_error_code' => $code,
        ]);

        $request = request();
        $next = function (Request $request) {
            $this->fail('It should not have entered the next() function');
        };

        $middleware = new DeviceChecker;
        try {
            $middleware->handle($request, $next);
            $this->fail('It should generate an exception');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}
