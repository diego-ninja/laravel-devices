<?php

namespace Ninja\DeviceTracker\Database\Seeders;

class DataFactory
{
    public static function browsers(): array
    {
        return [
            ['name' => 'Chrome', 'version' => '122.0.6261.112', 'family' => 'Chrome', 'engine' => 'Blink'],
            ['name' => 'Firefox', 'version' => '123.0', 'family' => 'Firefox', 'engine' => 'Gecko'],
            ['name' => 'Safari', 'version' => '17.3.1', 'family' => 'Safari', 'engine' => 'WebKit'],
            ['name' => 'Edge', 'version' => '122.0.2365.92', 'family' => 'Edge', 'engine' => 'Blink'],
        ];
    }

    public static function platforms(): array
    {
        return [
            ['name' => 'Windows', 'version' => '10.0', 'family' => 'Windows'],
            ['name' => 'Windows', 'version' => '11.0', 'family' => 'Windows'],
            ['name' => 'macOS', 'version' => '14.3.1', 'family' => 'macOS'],
            ['name' => 'iOS', 'version' => '17.3.1', 'family' => 'iOS'],
            ['name' => 'Android', 'version' => '14.0', 'family' => 'Android'],
            ['name' => 'Linux', 'version' => '6.7', 'family' => 'Linux'],
        ];
    }

    public static function deviceTypes(): array
    {
        return [
            ['family' => 'iPhone', 'model' => 'iPhone 15 Pro', 'type' => 'smartphone'],
            ['family' => 'iPhone', 'model' => 'iPhone 14', 'type' => 'smartphone'],
            ['family' => 'Samsung', 'model' => 'Galaxy S24 Ultra', 'type' => 'smartphone'],
            ['family' => 'iPad', 'model' => 'iPad Pro 12.9', 'type' => 'tablet'],
            ['family' => 'MacBook', 'model' => 'MacBook Pro M3', 'type' => 'desktop'],
            ['family' => 'Dell', 'model' => 'XPS 15', 'type' => 'desktop'],
        ];
    }

    public static function locations(): array
    {
        return [
            ['country' => 'ES', 'region' => 'Madrid', 'city' => 'Madrid', 'postal' => '28001', 'lat' => '40.4168', 'long' => '-3.7038', 'timezone' => 'Europe/Madrid'],
            ['country' => 'ES', 'region' => 'Cataluña', 'city' => 'Barcelona', 'postal' => '08001', 'lat' => '41.3851', 'long' => '2.1734', 'timezone' => 'Europe/Madrid'],
            ['country' => 'ES', 'region' => 'Andalucía', 'city' => 'Sevilla', 'postal' => '41001', 'lat' => '37.3891', 'long' => '-5.9845', 'timezone' => 'Europe/Madrid'],
            ['country' => 'FR', 'region' => 'Île-de-France', 'city' => 'Paris', 'postal' => '75001', 'lat' => '48.8566', 'long' => '2.3522', 'timezone' => 'Europe/Paris'],
            ['country' => 'UK', 'region' => 'England', 'city' => 'London', 'postal' => 'SW1A 1AA', 'lat' => '51.5074', 'long' => '-0.1278', 'timezone' => 'Europe/London'],
            ['country' => 'DE', 'region' => 'Berlin', 'city' => 'Berlin', 'postal' => '10115', 'lat' => '52.5200', 'long' => '13.4050', 'timezone' => 'Europe/Berlin'],
        ];
    }

    public static function userAgents(): array
    {
        return [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.112 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.112 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:123.0) Gecko/20100101 Firefox/123.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3.1 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.112 Mobile Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPad; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Version/15.0 Safari/605.1.15 AlohaBrowser/3.2.6',
            'Mozilla/5.0 (Wayland; Fedora Linux 38.20230825.0 Kinoite x86_64; rv:6.4.11-200.fc38.x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15',
            'Mozilla/5.0 (Wayland; Linux x86_64; System76 Galago Pro (galp2)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Ubuntu/24.04 Edg/122.0.2365.92',
            'Mozilla/7.0 (iPhone; CPU iPhone OS 18_7; iPhone 15 Pro Max) AppleWebKit/533.2 (KHTML, seperti Gecko) CriOS/432.0.8702.51 Seluler/15E148 Safari/804.17',
            'Mozilla/7.0 (iPhone; CPU iPhone OS 18_7; iPhone 15 Pro Max) AppleWebKit/533.2 (KHTML, like Gecko) CriOS/432.0.8702.51 Mobile/15E148 Safari/804.17',
            'Mozilla/5.0 (Linux; Android 13; 2211133G Build/TKQ1.220905.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/113.0.5672.76 Mobile Safari/537.36',
            'Mozilla/5.0 (Linux; U; Android 13; pl-pl; Xiaomi 13 Build/TKQ1.220905.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/100.0.4896.127 Mobile Safari/537.36 XiaoMi/MiuiBrowser/13.28.0-gn',
        ];
    }

    public static function commonPaths(): array
    {
        return [
            'auth' => ['/login', '/logout', '/register', '/password/reset', '/2fa/verify'],
            'dashboard' => ['/dashboard', '/home', '/overview', '/stats', '/analytics'],
            'profile' => ['/profile', '/settings', '/account', '/devices', '/security'],
            'api' => ['/api/v1/users', '/api/v1/devices', '/api/v1/sessions', '/api/v1/events'],
            'resources' => ['/users', '/organizations', '/teams', '/reports', '/audit-logs'],
        ];
    }

    public static function riskLevels(): array
    {
        return [
            'low' => [
                'score' => [0, 30],
                'factors' => [
                    'regular_usage_pattern',
                    'known_location',
                    'verified_device',
                    'consistent_ip',
                    'normal_activity_hours',
                ],
            ],
            'medium' => [
                'score' => [31, 70],
                'factors' => [
                    'unusual_login_time',
                    'new_location',
                    'multiple_failed_attempts',
                    'rapid_location_change',
                    'unusual_device_pattern',
                ],
            ],
            'high' => [
                'score' => [71, 100],
                'factors' => [
                    'suspicious_ip',
                    'tor_network',
                    'multiple_locations',
                    'brute_force_attempt',
                    'known_proxy',
                    'blacklisted_ip',
                    'impossible_travel',
                ],
            ],
        ];
    }

    public static function eventMetadata(): array
    {
        return [
            'login' => [
                'auth_providers' => ['local', 'google', 'github', 'microsoft', 'apple'],
                'success_rate' => 0.90,
                'failure_reasons' => [
                    'invalid_credentials',
                    'account_locked',
                    'ip_blocked',
                    'geo_restricted',
                    '2fa_failed',
                ],
            ],
            'page_view' => [
                'referrers' => [
                    'direct',
                    'https://www.google.com',
                    'https://www.facebook.com',
                    'https://www.twitter.com',
                    'https://www.linkedin.com',
                ],
                'viewports' => [
                    '1920x1080',
                    '1366x768',
                    '360x640',
                    '414x896',
                    '1536x864',
                ],
                'load_time_range' => [500, 3000],
            ],
            'submit' => [
                'form_types' => [
                    'contact',
                    'search',
                    'payment',
                    'upload',
                    'subscription',
                ],
                'success_rate' => 0.95,
            ],
            'click' => [
                'element_types' => [
                    'button',
                    'link',
                    'menu',
                    'tab',
                    'card',
                ],
                'interaction_time_range' => [100, 500],
            ],
            'api_request' => [
                'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
                'response_time_range' => [50, 1000],
                'success_rate' => 0.98,
            ],
        ];
    }

    public static function ipRanges(): array
    {
        return [
            'safe' => [
                '192.168.0.0/16',
                '10.0.0.0/8',
                '172.16.0.0/12',
            ],
            'suspicious' => [
                '185.156.73.0/24',  // Known VPN range
                '193.238.46.0/24',  // Tor exit nodes
                '45.133.1.0/24',     // Known proxy range
            ],
        ];
    }

    public static function languages(): array
    {
        return ['en-US', 'es-ES', 'fr-FR', 'de-DE', 'it-IT', 'pt-PT', 'nl-NL', 'ru-RU', 'ja-JP', 'zh-CN'];
    }

    public static function timezones(): array
    {
        return [
            'Europe/Madrid',
            'Europe/London',
            'Europe/Paris',
            'Europe/Berlin',
            'America/New_York',
            'America/Los_Angeles',
            'Asia/Tokyo',
            'Asia/Shanghai',
            'Australia/Sydney',
            'Pacific/Auckland',
        ];
    }
}
