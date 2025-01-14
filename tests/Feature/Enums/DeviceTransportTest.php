<?php

namespace Ninja\DeviceTracker\Tests\Feature\Enums;

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
}
