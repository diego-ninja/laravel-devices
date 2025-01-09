<?php

namespace Ninja\DeviceTracker\Tests\Feature\Http\Middleware;

use Faker\Provider\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException;
use Ninja\DeviceTracker\Http\Middleware\DeviceTracker;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeviceTrackerTest extends FeatureTestCase
{
    public const DEVICE_ID_PARAMETER = 'laravel_device_id';

    public static function nullUserAgentDataProvider(): array
    {
        $prefix = 'null_ua_';
        return [
            // Unknown Devices not allowed, No regeneration
            $prefix . 'unknown devices not allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => null,
                'exceptionClass' => HttpException::class,
            ],
            $prefix . 'unknown devices not allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => null,
                'exceptionClass' => UnknownDeviceDetectedException::class,
            ],
            // Unknown Devices not allowed, Regeneration
            $prefix . 'unknown devices not allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => null,
                'exceptionClass' => HttpException::class,
            ],
            $prefix . 'unknown devices not allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => null,
                'exceptionClass' => UnknownDeviceDetectedException::class,
            ],

            // Unknown Devices allowed, No regeneration
            $prefix . 'unknown devices allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => null,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => null,
                'exceptionClass' => null,
            ],
            // Unknown Devices allowed, Regeneration
            $prefix . 'unknown devices allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => null,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => null,
                'exceptionClass' => null,
            ],
        ];
    }

    public static function invalidUserAgentDataProvider(): array
    {
        $invalidUserAgent = 'absolutely_invalid_user_agent';
        $prefix = 'invalid_ua_';
        return [
            // Unknown Devices not allowed, No regeneration
            $prefix . 'unknown devices not allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => HttpException::class,
            ],
            $prefix . 'unknown devices not allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => UnknownDeviceDetectedException::class,
            ],
            // Unknown Devices not allowed, Regeneration
            $prefix . 'unknown devices not allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => HttpException::class,
            ],
            $prefix . 'unknown devices not allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => UnknownDeviceDetectedException::class,
            ],

            // Unknown Devices allowed, No regeneration
            $prefix . 'unknown devices allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => null,
            ],
            // Unknown Devices allowed, Regeneration
            $prefix . 'unknown devices allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $invalidUserAgent,
                'exceptionClass' => null,
            ],
        ];
    }

    public static function validUserAgentDataProvider(): array
    {
        $userAgent = UserAgent::userAgent();
        $prefix = 'valid ua, ';
        return [
            // Unknown Devices not allowed, No regeneration
            $prefix . 'unknown devices not allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices not allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            // Unknown Devices not allowed, Regeneration
            $prefix . 'unknown devices not allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices not allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => false,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],

            // Unknown Devices allowed, No regeneration
            $prefix . 'unknown devices allowed, no regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, no regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => false,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            // Unknown Devices allowed, Regeneration
            $prefix . 'unknown devices allowed, regeneration, 403' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => false,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
            $prefix . 'unknown devices allowed, regeneration, exception' => [
                'config' => [
                    'devices.regenerate_devices' => true,
                    'devices.middlewares.device-tracker.exception_on_unknown_devices' => true,
                    'devices.allow_unknown_devices' => true,
                ],
                'userAgent' => $userAgent,
                'exceptionClass' => null,
            ],
        ];
    }

    #[DataProvider('nullUserAgentDataProvider')]
    #[DataProvider('invalidUserAgentDataProvider')]
    #[DataProvider('validUserAgentDataProvider')]
    public function testWithUserAgent(
        array $config,
        ?string $userAgent = null,
        ?string $exceptionClass = null
    ): void {
        $config = array_merge($config, [
            'devices.device_id_transport' => 'cookie',
            'devices.device_id_parameter' => self::DEVICE_ID_PARAMETER,
        ]);
        $this->setConfig($config);

        $request = request();
        $request->headers->set('User-Agent', $userAgent);

        $next = function (Request $nextRequest) use ($exceptionClass, $userAgent) {
            if (!empty($exceptionClass)) {
                $this->fail('It should not enter in the next middleware');
            }

            $this->assertNull($nextRequest->headers->get(self::DEVICE_ID_PARAMETER));
            return new Response(null, 200);
        };
        if (!empty($exceptionClass)) {
            $this->expectException($exceptionClass);
        }

        // Nothing happened yet
        $this->assertNull(device_uuid());
        $middleware = new DeviceTracker;
        /** @var Response $response */
        $response = $middleware->handle($request, $next);

        $this->assertNotNull(device_uuid());
        $this->assertTrue($response->headers->has('set-cookie'));
        $cookies = $response->headers->getCookies();
        $this->assertStringStartsWith(self::DEVICE_ID_PARAMETER, implode(',', $cookies));
        $this->assertEquals(0, Device::query()->count());
    }
}
