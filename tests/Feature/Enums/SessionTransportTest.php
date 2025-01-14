<?php

namespace Ninja\DeviceTracker\Tests\Feature\Enums;

use Ninja\DeviceTracker\Enums\SessionTransport;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SessionTransportTest extends FeatureTestCase
{
    public static function hierarchy_provider(): array
    {
        $uuid = 'f765e4d4-a990-4c59-aeed-d16f0aed2665';

        return [
            'undefined_hierarchy_unset_id' => [
                'hierarchy' => [],
                'expectedTransport' => SessionTransport::Cookie,
                'expectedId' => null,
            ],
            'undefined_hierarchy_set_id_cookie' => [
                'hierarchy' => [],
                'expectedTransport' => SessionTransport::Cookie,
                'expectedId' => $uuid,
                'cookie' => $uuid,
            ],
            'request_cookie_unset_id' => [
                'hierarchy' => [
                    SessionTransport::Request->value,
                    SessionTransport::Cookie->value,
                ],
                'expectedTransport' => SessionTransport::Request,
                'expectedId' => null,
            ],
            'request_cookie_set_id_request' => [
                'hierarchy' => [
                    SessionTransport::Request->value,
                    SessionTransport::Cookie->value,
                ],
                'expectedTransport' => SessionTransport::Request,
                'expectedId' => $uuid,
                'input' => $uuid,
            ],
            'request_cookie_set_id_cookie' => [
                'hierarchy' => [
                    SessionTransport::Request->value,
                    SessionTransport::Cookie->value,
                ],
                'expectedTransport' => SessionTransport::Cookie,
                'expectedId' => $uuid,
                'cookie' => $uuid,
            ],
            'request_cookie_set_id_header' => [
                'hierarchy' => [
                    SessionTransport::Request->value,
                    SessionTransport::Cookie->value,
                ],
                'expectedTransport' => SessionTransport::Request,
                'expectedId' => null,
                'header' => $uuid,
            ],
            'header_set_id_header' => [
                'hierarchy' => [
                    SessionTransport::Header->value,
                ],
                'expectedTransport' => SessionTransport::Header,
                'expectedId' => $uuid,
                'header' => $uuid,
            ],
            'session_set_id_session' => [
                'hierarchy' => [
                    SessionTransport::Session->value,
                ],
                'expectedTransport' => SessionTransport::Session,
                'expectedId' => $uuid,
                'session' => $uuid,
            ],
        ];
    }

    #[DataProvider('hierarchy_provider')]
    public function test_current_with_hierarchy(
        array $hierarchy,
        SessionTransport $expectedTransport,
        ?string $expectedId,
        ?string $cookie = null,
        ?string $header = null,
        ?string $input = null,
        ?string $session = null,
    ): void {
        $parameter = 'session_id';
        $this->setConfig([
            'devices.session_id_transport_hierarchy' => $hierarchy,
            'devices.session_id_parameter' => $parameter,
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

        $transport = SessionTransport::current();

        $this->assertEquals($expectedTransport, $transport);

        $id = SessionTransport::getIdFromHierarchy();

        if (is_null($expectedId)) {
            $this->assertNull($id);
        } else {
            $this->assertEquals($expectedId, $id?->toString());
        }
    }
}
