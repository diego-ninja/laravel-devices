<?php

namespace Ninja\DeviceTracker\Tests\Feature\Enums;

use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Enums\DeviceTransport;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DeviceTransportTest extends FeatureTestCase
{
    public static function hierarchy_provider(): array
    {
        $uuid = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        return [
            'undefined_hierarchy_unset_id' => [
                'hierarchy' => [],
                'expectedTransport' => DeviceTransport::Cookie,
                'expectedId' => null,
            ],
            'undefined_hierarchy_set_id_cookie' => [
                'hierarchy' => [],
                'expectedTransport' => DeviceTransport::Cookie,
                'expectedId' => $uuid,
                'cookie' => $uuid,
            ],
            'request_cookie_unset_id' => [
                'hierarchy' => [
                    DeviceTransport::Request->value,
                    DeviceTransport::Cookie->value,
                ],
                'expectedTransport' => DeviceTransport::Request,
                'expectedId' => null,
            ],
            'request_cookie_set_id_request' => [
                'hierarchy' => [
                    DeviceTransport::Request->value,
                    DeviceTransport::Cookie->value,
                ],
                'expectedTransport' => DeviceTransport::Request,
                'expectedId' => $uuid,
                'input' => $uuid,
            ],
            'request_cookie_set_id_cookie' => [
                'hierarchy' => [
                    DeviceTransport::Request->value,
                    DeviceTransport::Cookie->value,
                ],
                'expectedTransport' => DeviceTransport::Cookie,
                'expectedId' => $uuid,
                'cookie' => $uuid,
            ],
            'request_cookie_set_id_header' => [
                'hierarchy' => [
                    DeviceTransport::Request->value,
                    DeviceTransport::Cookie->value,
                ],
                'expectedTransport' => DeviceTransport::Request,
                'expectedId' => null,
                'header' => $uuid,
            ],
            'header_set_id_header' => [
                'hierarchy' => [
                    DeviceTransport::Header->value,
                ],
                'expectedTransport' => DeviceTransport::Header,
                'expectedId' => $uuid,
                'header' => $uuid,
            ],
            'session_set_id_session' => [
                'hierarchy' => [
                    DeviceTransport::Session->value,
                ],
                'expectedTransport' => DeviceTransport::Session,
                'expectedId' => $uuid,
                'session' => $uuid,
            ],
        ];
    }

    #[DataProvider('hierarchy_provider')]
    public function test_current_with_hierarchy(
        array $hierarchy,
        DeviceTransport $expectedTransport,
        ?string $expectedId,
        ?string $cookie = null,
        ?string $header = null,
        ?string $input = null,
        ?string $session = null,
    ): void {
        $parameter = 'device_id';
        $this->setConfig([
            'devices.device_id_transport_hierarchy' => $hierarchy,
            'devices.device_id_parameter' => $parameter,
        ]);

        if (isset($cookie)) {
            request()->cookies->set($parameter, $cookie);
        }
        if (isset($header)) {
            request()->headers->set($parameter, $header);
        }
        if (isset($input)) {
            request()->merge([$parameter => $input]);
        }
        if (isset($session)) {
            session()->put($parameter, $session);
        }

        $transport = DeviceTransport::current();

        $this->assertEquals($expectedTransport, $transport);

        $id = DeviceTransport::getIdFromHierarchy();

        if (is_null($expectedId)) {
            $this->assertNull($id);
        } else {
            $this->assertEquals($expectedId, $id?->toString());
        }
    }

    public function test_current_with_non_encrypted_cookie(): void
    {
        $parameter = 'device_id';
        $this->setConfig([
            'devices.device_id_transport_hierarchy' => [DeviceTransport::Cookie->value],
            'devices.device_id_parameter' => $parameter,
        ]);
        $id = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        request()->cookies->set($parameter, $id);

        $transport = DeviceTransport::current();

        $this->assertEquals(DeviceTransport::Cookie, $transport);

        $storableId = DeviceTransport::getIdFromHierarchy();

        $this->assertTrue($storableId instanceof StorableId);
        $this->assertEquals($id, $storableId);
    }

    public function test_current_with_encrypted_cookie(): void
    {
        $parameter = 'device_id';
        $key = 'base64:Lzrm+AkE+RrRJWDHON58e8unP7LBK6PlyyLo5k4i6Q0=';
        $this->setConfig([
            'devices.device_id_transport_hierarchy' => [DeviceTransport::Cookie->value],
            'devices.device_id_parameter' => $parameter,
            'app.key' => $key,
        ]);
        $id = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';
        $encrypter = new Encrypter(base64_decode(Str::after($key, 'base64:')), 'AES-256-CBC');
        $encryptedCookie = $encrypter->encrypt(
            value: CookieValuePrefix::create($parameter, $encrypter->getKey()).$id,
            serialize: false,
        );

        request()->cookies->set($parameter, $encryptedCookie);

        $transport = DeviceTransport::current();

        $this->assertEquals(DeviceTransport::Cookie, $transport);

        $storableId = DeviceTransport::getIdFromHierarchy();

        $this->assertTrue($storableId instanceof StorableId);
        $this->assertEquals($id, $storableId);
    }

    public function test_current_from_alternative_parameter(): void
    {
        $parameter = 'device_id';
        $this->setConfig([
            'devices.device_id_transport_hierarchy' => [DeviceTransport::Cookie->value],
            'devices.device_id_parameter' => 'invalid_parameter',
            'devices.device_id_alternative_parameter' => $parameter,
        ]);
        $id = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        request()->cookies->set($parameter, $id);

        $transport = DeviceTransport::current();

        $this->assertEquals(DeviceTransport::Cookie, $transport);

        $storableId = DeviceTransport::getIdFromHierarchy();

        $this->assertTrue($storableId instanceof StorableId);
        $this->assertEquals($id, $storableId);
    }

    public function test_forget_device_from_session(): void
    {
        $parameter = 'device_id';
        $this->setConfig([
            'devices.device_id_response_transport' => DeviceTransport::Session->value,
            'devices.device_id_parameter' => $parameter,
        ]);
        $id = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        Session::start();
        Session::put($parameter, $id);

        DeviceTransport::forget();

        $this->assertNull(Session::get($parameter));
    }

    public function test_forget_device_from_cookie(): void
    {
        $parameter = 'device_id';
        $this->setConfig([
            'devices.device_id_response_transport' => DeviceTransport::Cookie->value,
            'devices.device_id_parameter' => $parameter,
        ]);
        $id = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        Cookie::queue(
            Cookie::forever(
                name: $parameter,
                value: $id,
            ),
        );

        $cookies = Cookie::getQueuedCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $parameter) {
                $this->assertEquals($id, $cookie->getValue());
            }
        }

        DeviceTransport::forget();

        $cookies = Cookie::getQueuedCookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $parameter) {
                $this->assertNull($cookie->getValue());
            }
        }
    }
}
