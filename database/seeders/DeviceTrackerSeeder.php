<?php

namespace Ninja\DeviceTracker\Database\Seeders;

use DateTime;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ninja\DeviceTracker\Enums\DeviceStatus;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Modules\Detection\Device\UserAgentDeviceDetector;
use Ninja\DeviceTracker\Modules\Location\Contracts\LocationProvider;
use Ninja\DeviceTracker\Modules\Location\DTO\Location;
use Ninja\DeviceTracker\Modules\Tracking\Enums\EventType;
use Ninja\DeviceTracker\ValueObject\DeviceId;
use Ninja\DeviceTracker\ValueObject\EventId;
use Ninja\DeviceTracker\ValueObject\SessionId;

class DeviceTrackerSeeder extends Seeder
{
    private Generator $faker;

    private UserAgentDeviceDetector $deviceDetector;

    private LocationProvider $locationProvider;

    private array $realisticUserAgents;

    private const USER_COUNT = 50;

    private const MAX_DEVICES_PER_USER = 4;

    private const MAX_SESSIONS_PER_DEVICE = 10;

    private const MAX_EVENTS_PER_SESSION = 20;

    private const DEVICE_STATUS_RATIOS = [
        DeviceStatus::Verified->value => 70,
        DeviceStatus::Unverified->value => 20,
        DeviceStatus::Hijacked->value => 5,
        DeviceStatus::Inactive->value => 5,
    ];

    private const SESSION_STATUS_RATIOS = [
        SessionStatus::Active->value => 60,
        SessionStatus::Inactive->value => 20,
        SessionStatus::Finished->value => 15,
        SessionStatus::Blocked->value => 3,
        SessionStatus::Locked->value => 2,
    ];

    private const EVENT_TYPE_RATIOS = [
        EventType::Login->value => 5,
        EventType::Logout->value => 5,
        EventType::PageView->value => 60,
        EventType::Click->value => 15,
        EventType::Submit->value => 10,
        EventType::ApiRequest->value => 5,
    ];

    public function __construct(UserAgentDeviceDetector $deviceDetector, LocationProvider $locationProvider)
    {
        $this->faker = Faker::create();
        $this->deviceDetector = $deviceDetector;
        $this->locationProvider = $locationProvider;

        $this->realisticUserAgents = $this->getRealisticUserAgents();
    }

    private function getRealisticUserAgents(): array
    {
        return [
            // Chrome Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 11.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',

            // Chrome macOS
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Apple M1 Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',

            // Firefox Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (Windows NT 11.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',

            // Firefox macOS
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:123.0) Gecko/20100101 Firefox/123.0',

            // Safari macOS
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Safari/605.1.15',
            'Mozilla/5.0 (Macintosh; Apple M1 Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Safari/605.1.15',

            // Safari iOS
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',

            // Chrome Android
            'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',

            // Edge Windows
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.2365.92',

            // Chrome OS
            'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',

            // Linux
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:123.0) Gecko/20100101 Firefox/123.0',
        ];
    }

    private function generateRealisticIp(): string
    {
        $ipPools = [
            ['185.86.', '80.58.', '85.48.', '88.27.'],
            ['188.65.', '195.25.', '92.103.'],
            ['176.58.', '185.121.', '92.242.'],
            ['178.203.', '185.72.', '91.205.'],
            ['104.196.', '35.186.', '104.237.'],
        ];

        $pool = $this->faker->randomElement($ipPools);

        return $pool[array_rand($pool)].
            $this->faker->numberBetween(0, 255).'.'.
            $this->faker->numberBetween(0, 255);
    }

    public function run(): void
    {
        $this->command->info('Starting Device Tracker Seeder...');
        $this->command->info('Creating users and their digital footprint...');
        $bar = $this->command->getOutput()->createProgressBar(self::USER_COUNT);

        for ($i = 0; $i < self::USER_COUNT; $i++) {
            $this->createUserWithDigitalFootprint();
            $bar->advance();
        }

        $bar->finish();
        $this->command->info("\nSeeding completed successfully!");
    }

    private function createUserWithDigitalFootprint(): void
    {
        $createdAt = $this->faker->dateTimeBetween('-1 year');

        $userId = DB::table('users')->insertGetId([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'email_verified_at' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $numDevices = $this->faker->numberBetween(1, self::MAX_DEVICES_PER_USER);
        for ($d = 0; $d < $numDevices; $d++) {
            $this->createDeviceWithSessions($userId, $createdAt);
        }
    }

    private function createDeviceWithSessions(int $userId, DateTime $userCreatedAt): void
    {
        $deviceUuid = DeviceId::build()->toString();
        $deviceCreatedAt = $this->faker->dateTimeBetween($userCreatedAt);
        $userAgent = $this->faker->randomElement($this->realisticUserAgents);

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
        ]);

        $deviceInfo = $this->deviceDetector->detect($request);

        if (! $deviceInfo) {
            return; // Skip if device detection fails
        }

        $status = $this->getRandomWeighted(self::DEVICE_STATUS_RATIOS);
        $ip_address = $this->generateRealisticIp();

        DB::table('devices')->insert([
            'uuid' => $deviceUuid,
            'status' => $status,
            'browser' => $deviceInfo->browser->name,
            'browser_version' => (string) $deviceInfo->browser->version,
            'browser_family' => $deviceInfo->browser->family,
            'browser_engine' => $deviceInfo->browser->engine,
            'platform' => $deviceInfo->platform->name,
            'platform_version' => (string) $deviceInfo->platform->version,
            'platform_family' => $deviceInfo->platform->family,
            'device_type' => $deviceInfo->device->type,
            'device_family' => $deviceInfo->device->family,
            'device_model' => $deviceInfo->device->model,
            'grade' => $deviceInfo->grade,
            'source' => $userAgent,
            'fingerprint' => hash('xxh128', $userAgent.$deviceUuid),
            'created_at' => $deviceCreatedAt,
            'updated_at' => $deviceCreatedAt,
            'verified_at' => $status === DeviceStatus::Verified->value ? $deviceCreatedAt : null,
            'hijacked_at' => $status === DeviceStatus::Hijacked->value ?
                $this->faker->dateTimeBetween($deviceCreatedAt) : null,
        ]);

        $numSessions = $this->faker->numberBetween(1, self::MAX_SESSIONS_PER_DEVICE);
        for ($s = 0; $s < $numSessions; $s++) {
            $this->createSessionWithEvents($userId, $deviceUuid, $deviceCreatedAt, $userAgent, $ip_address);
        }
    }

    private function createSessionWithEvents(
        int $userId,
        string $deviceUuid,
        DateTime $deviceCreatedAt,
        string $userAgent,
        ?string $ip = null
    ): void {
        $sessionUuid = SessionId::build()->toString();
        $startedAt = $this->faker->dateTimeBetween($deviceCreatedAt);
        $sessionStatus = $this->getRandomWeighted(self::SESSION_STATUS_RATIOS);

        $ip = $ip ?? $this->generateRealisticIp();

        try {
            $location = $this->locationProvider->locate($ip);
        } catch (\Exception $e) {
            $location = $this->faker->randomElement(DataFactory::locations());
            $location = Location::fromArray($location);
        }

        $lastActivityAt = $this->faker->dateTimeBetween($startedAt);
        $finishedAt = $sessionStatus == SessionStatus::Finished->value ? $this->faker->dateTimeBetween($lastActivityAt) : null;
        $blockedAt = $sessionStatus === SessionStatus::Blocked->value ? $this->faker->dateTimeBetween($lastActivityAt) : null;

        DB::table('device_sessions')->insert([
            'uuid' => $sessionUuid,
            'user_id' => $userId,
            'device_uuid' => $deviceUuid,
            'ip' => $ip,
            'location' => json_encode($location),
            'status' => $sessionStatus,
            'metadata' => json_encode([
                'user_agent' => $userAgent,
                'client' => [
                    'timezone' => $location->timezone,
                    'language' => $this->faker->randomElement(DataFactory::languages()),
                    'screen' => $this->faker->randomElement(
                        DataFactory::eventMetadata()['page_view']['viewports']
                    ),
                ],
            ]),
            'started_at' => $startedAt,
            'last_activity_at' => $lastActivityAt,
            'finished_at' => $finishedAt,
            'blocked_at' => $blockedAt,
        ]);

        $this->createEventsForSession(
            sessionUuid: $sessionUuid,
            deviceUuid: $deviceUuid,
            startedAt: $startedAt,
            endAt: $finishedAt ?? $lastActivityAt,
            ip: $ip
        );
    }

    private function createEventsForSession(
        string $sessionUuid,
        string $deviceUuid,
        DateTime $startedAt,
        DateTime $endAt,
        string $ip
    ): void {
        $this->createEvent(
            sessionUuid: $sessionUuid,
            deviceUuid: $deviceUuid,
            type: EventType::Login,
            occurredAt: $startedAt,
            ip: $ip
        );

        $numEvents = $this->faker->numberBetween(5, self::MAX_EVENTS_PER_SESSION - 2);
        for ($i = 0; $i < $numEvents; $i++) {
            $type = EventType::from($this->getRandomWeighted(self::EVENT_TYPE_RATIOS));
            $this->createEvent(
                sessionUuid: $sessionUuid,
                deviceUuid: $deviceUuid,
                type: $type,
                occurredAt: $this->faker->dateTimeBetween($startedAt, $endAt),
                ip: $ip
            );
        }

        if ($endAt->diff(new DateTime('now'))->invert === 1) {
            $this->createEvent(
                sessionUuid: $sessionUuid,
                deviceUuid: $deviceUuid,
                type: EventType::Logout,
                occurredAt: $endAt,
                ip: $ip
            );
        }
    }

    private function createEvent(
        string $sessionUuid,
        string $deviceUuid,
        EventType $type,
        DateTime $occurredAt,
        string $ip
    ): void {
        $eventMetadata = DataFactory::eventMetadata();
        $paths = DataFactory::commonPaths();

        $metadata = match ($type) {
            EventType::Login => [
                'auth' => [
                    'provider' => $this->faker->randomElement($eventMetadata['login']['auth_providers']),
                    'success' => true,
                ],
            ],
            EventType::PageView => [
                'page' => [
                    'path' => $this->faker->randomElement($paths['dashboard']),
                    'load_time' => $this->faker->numberBetween(
                        $eventMetadata['page_view']['load_time_range'][0],
                        $eventMetadata['page_view']['load_time_range'][1]
                    ),
                ],
            ],
            EventType::Click => [
                'element' => [
                    'type' => $this->faker->randomElement($eventMetadata['click']['element_types']),
                    'id' => 'btn_'.$this->faker->word,
                ],
            ],
            default => []
        };

        DB::table('device_events')->insert([
            'uuid' => EventId::build()->toString(),
            'device_uuid' => $deviceUuid,
            'session_uuid' => $sessionUuid,
            'type' => $type->value,
            'ip_address' => $ip,
            'metadata' => json_encode($metadata),
            'occurred_at' => $occurredAt,
        ]);
    }

    private function calculateDeviceGrade(array $browser, array $platform): string
    {
        $browserAge = $this->calculateSoftwareAge($browser['version']);
        $platformAge = $this->calculateSoftwareAge($platform['version']);

        return match (true) {
            $browserAge <= 30 && $platformAge <= 180 => 'A',
            $browserAge <= 90 && $platformAge <= 365 => 'B',
            $browserAge <= 180 && $platformAge <= 730 => 'C',
            default => 'D'
        };
    }

    private function calculateSoftwareAge(string $version): int
    {
        return $this->faker->numberBetween(1, 1000);
    }

    private function getRandomWeighted(array $weights): int|string|null
    {
        $total = array_sum($weights);
        $random = $this->faker->numberBetween(1, $total);
        $current = 0;

        foreach ($weights as $value => $weight) {
            $current += $weight;
            if ($random <= $current) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
