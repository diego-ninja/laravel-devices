<?php

namespace Ninja\DeviceTracker\Tests\Feature\Traits;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Tests\FeatureTestCase;
use Ninja\DeviceTracker\Traits\HasDevices;

class HasDevicesTest extends FeatureTestCase
{
    public function test_one_device_with_multiple_sessions(): void
    {
        $user = new class extends User
        {
            use HasDevices;

            protected $table = 'users';
        };
        $user->name = 'test';
        $user->email = 'john@doe.com';
        $user->password = 'password';
        $user->save();

        $device = Device::factory()->create();
        $session1 = new Session([
            'uuid' => SessionIdFactory::generate(),
            'user_id' => $user->id,
            'device_uuid' => $device->uuid,
            'ip' => '192.168.0.0',
            'location' => new Location(null, null, null, null, null, null, null, null, null, null),
            'status' => SessionStatus::Active,
            'metadata' => new Metadata([]),
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
        $session2 = new Session([
            'uuid' => SessionIdFactory::generate(),
            'user_id' => $user->id,
            'device_uuid' => $device->uuid,
            'ip' => '192.168.0.1',
            'location' => new Location(null, null, null, null, null, null, null, null, null, null),
            'status' => SessionStatus::Active,
            'metadata' => new Metadata([]),
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);

        $session1->save();
        $session2->save();

        $this->assertCount(1, $user->devices);
        $this->assertEquals(1, Device::all()->count());
        $this->assertEquals(2, Session::all()->count());
    }

    public function test_multiple_devices_with_multiple_sessions(): void
    {
        $user = new class extends User
        {
            use HasDevices;

            protected $table = 'users';
        };
        $user->name = 'test';
        $user->email = 'john@doe.com';
        $user->password = 'password';
        $user->save();

        $devicesCount = 5;

        for ($i = 0; $i < $devicesCount; $i++) {
            $device = Device::factory()->create();
            $session1 = new Session([
                'uuid' => SessionIdFactory::generate(),
                'user_id' => $user->id,
                'device_uuid' => $device->uuid,
                'ip' => '192.168.0.0',
                'location' => new Location(null, null, null, null, null, null, null, null, null, null),
                'status' => SessionStatus::Active,
                'metadata' => new Metadata([]),
                'started_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]);
            $session2 = new Session([
                'uuid' => SessionIdFactory::generate(),
                'user_id' => $user->id,
                'device_uuid' => $device->uuid,
                'ip' => '192.168.0.1',
                'location' => new Location(null, null, null, null, null, null, null, null, null, null),
                'status' => SessionStatus::Active,
                'metadata' => new Metadata([]),
                'started_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]);

            $session1->save();
            $session2->save();
        }

        $this->assertCount($devicesCount, $user->devices);
        $this->assertEquals($devicesCount, Device::all()->count());
        $this->assertEquals($devicesCount * 2, Session::all()->count());
    }

    public function test_multiple_devices_with_multiple_sessions_and_with_unrelated_devices_and_sessions(): void
    {
        $user = new class extends User
        {
            use HasDevices;

            protected $table = 'users';
        };

        $user->name = 'test';
        $user->email = 'john@doe.com';
        $user->password = 'password';
        $user->save();

        $user2 = new class extends User
        {
            use HasDevices;

            protected $table = 'users';
        };

        $user2->name = 'test2';
        $user2->email = 'john2@doe.com';
        $user2->password = 'password2';
        $user2->save();

        $devicesCount = 5;

        $device = Device::factory()->create();
        $session1 = new Session([
            'uuid' => SessionIdFactory::generate(),
            'user_id' => $user2->id,
            'device_uuid' => $device->uuid,
            'ip' => '192.168.0.0',
            'location' => new Location(null, null, null, null, null, null, null, null, null, null),
            'status' => SessionStatus::Active,
            'metadata' => new Metadata([]),
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);
        $session2 = new Session([
            'uuid' => SessionIdFactory::generate(),
            'user_id' => $user2->id,
            'device_uuid' => $device->uuid,
            'ip' => '192.168.0.1',
            'location' => new Location(null, null, null, null, null, null, null, null, null, null),
            'status' => SessionStatus::Active,
            'metadata' => new Metadata([]),
            'started_at' => Carbon::now(),
            'last_activity_at' => Carbon::now(),
        ]);

        $session1->save();
        $session2->save();

        for ($i = 0; $i < $devicesCount; $i++) {
            $device = Device::factory()->create();
            $session1 = new Session([
                'uuid' => SessionIdFactory::generate(),
                'user_id' => $user->id,
                'device_uuid' => $device->uuid,
                'ip' => '192.168.0.0',
                'location' => new Location(null, null, null, null, null, null, null, null, null, null),
                'status' => SessionStatus::Active,
                'metadata' => new Metadata([]),
                'started_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]);
            $session2 = new Session([
                'uuid' => SessionIdFactory::generate(),
                'user_id' => $user->id,
                'device_uuid' => $device->uuid,
                'ip' => '192.168.0.1',
                'location' => new Location(null, null, null, null, null, null, null, null, null, null),
                'status' => SessionStatus::Active,
                'metadata' => new Metadata([]),
                'started_at' => Carbon::now(),
                'last_activity_at' => Carbon::now(),
            ]);

            $session1->save();
            $session2->save();
        }

        $this->assertCount($devicesCount, $user->devices);
        $this->assertCount(1, $user2->devices);
        $this->assertEquals($devicesCount + 1, Device::all()->count());
        $this->assertEquals($devicesCount * 2 + 2, Session::all()->count());
    }
}
