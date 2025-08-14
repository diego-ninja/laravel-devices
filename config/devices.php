<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parameter name for current user device tracking
    |--------------------------------------------------------------------------
    | This option specifies the name of the cookie that will be used to transport
    | the device id of the current user.
    |
    */
    'device_id_parameter' => 'laravel_device_id',

    /*
    |--------------------------------------------------------------------------
    | Alternative parameter name for user device tracking
    |--------------------------------------------------------------------------
    | This option is used as a backup key to search for the device id. If the
    | 'device_id_parameter' is not set then this parameter is searched.
    | This option is also useful when migrating the parameter name, making sure
    | that devices still using the old parameter can still be identified
    | through it.
    |
    */
    'device_id_alternative_parameter' => null,

    /*
    |--------------------------------------------------------------------------
    | Hierarchy of transports for device id
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the device id in order of priority.
    | When searching for a device id, the first transport method that have the device id set will determine
    | the device id.
    | By default only 'cookie' transport is used.
    |
    | Possible array values: 'cookie', 'header', 'session', 'request'
    |
    */
    'device_id_transport_hierarchy' => [
        'cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport for device id in the response
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the device id when sending
    | the response.
    | By default the 'cookie' transport is used.
    |
    | Possible values: 'cookie', 'header', 'session'
    |
    */
    'device_id_response_transport' => 'cookie',

    /*
    |--------------------------------------------------------------------------
    | Parameter name for current user session tracking
    |--------------------------------------------------------------------------
    | This option specifies the name of the parameter that will be used to transport
    | the session id for the current user.
    |
    */
    'session_id_parameter' => 'laravel_session_id',

    /*
    |--------------------------------------------------------------------------
    | Alternative parameter name for user session tracking
    |--------------------------------------------------------------------------
    | This option is used as a backup key to search for the session id. If the
    | 'session_id_parameter' is not set then this parameter is searched.
    | This option is also useful when migrating the parameter name, making sure
    | that devices still using the old parameter can still be identified
    | through it.
    |
    */
    'session_id_alternative_parameter' => null,

    /*
    |--------------------------------------------------------------------------
    | Hierarchy of transports for session id
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the session id in order of priority.
    | When searching for a session id, the first transport method that have the session id set will determine
    | the session id.
    | By default only 'cookie' transport is used
    |
    | Possible array values: 'cookie', 'header', 'session', 'request'
    |
    */
    'session_id_transport_hierarchy' => [
        'cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport for session id in the response
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the session id when sending
    | the response.
    | By default the 'cookie' transport is used.
    |
    | Possible values: 'cookie', 'header', 'session'
    |
    */
    'session_id_response_transport' => 'cookie',

    /*
    |--------------------------------------------------------------------------
    | Parameter name for client fingerprint
    |--------------------------------------------------------------------------
    | This option specifies the name of the parameter that will be used to read
    | the client fingerprint of the current device.
    |
    */
    'fingerprint_parameter' => 'laravel_client_fingerprint',

    /*
    |--------------------------------------------------------------------------
    | Alternative parameter name for client fingerprint
    |--------------------------------------------------------------------------
    | This option is used as a backup key to search for the client_fingerprint. If the
    | 'client_fingerprint_parameter' is not set then this parameter is searched.
    | This option is also useful when migrating the parameter name, making sure
    | that devices still using the old parameter can still be identified
    | through it.
    |
    */
    'fingerprint_alternative_parameter' => null,

    /*
    |--------------------------------------------------------------------------
    | Hierarchy of transports for client_fingerprint
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the client fingerprint in
    | order of priority.
    | When searching for a client fingerprint, the first transport method that
    | have a value set will determine the client fingerprint.
    | By default only 'cookie' transport is used.
    |
    | Possible array values: 'cookie', 'header', 'session', 'request'
    |
    */
    'fingerprint_transport_hierarchy' => [
        'cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport for client fingerprint in the response
    |--------------------------------------------------------------------------
    | This option specifies the transport method for the client fingerprint
    | when sending the response.
    | By default the 'cookie' transport is used.
    |
    | Possible values: 'cookie', 'header', 'session'
    |
    */
    'fingerprint_response_transport' => 'cookie',

    /*
    |--------------------------------------------------------------------------
    | Device ID class
    |--------------------------------------------------------------------------
    | This option specifies the class that will be used to store
    | and serialize the device id. Must implement the StorableId interface.
    |
    */
    'device_id_storable_class' => \Ninja\DeviceTracker\ValueObject\DeviceId::class,

    /*
    |--------------------------------------------------------------------------
    | Session ID class
    |--------------------------------------------------------------------------
    | This option specifies the class that will be used to store
    | and serialize the session id. Must implement the StorableId interface.
    |
    */
    'session_id_storable_class' => \Ninja\DeviceTracker\ValueObject\SessionId::class,

    /*
    |--------------------------------------------------------------------------
    | Event ID class
    |--------------------------------------------------------------------------
    | This option specifies the class that will be used to store
    | and serialize the event id. Must implement the StorableId interface.
    |
    */
    'event_id_storable_class' => \Ninja\DeviceTracker\ValueObject\EventId::class,

    /*
    |--------------------------------------------------------------------------
    | Client fingerprint class
    |--------------------------------------------------------------------------
    | This option specifies the class that will be used to store and serialize
    | the client fingerprint. Must implement the StorableId interface.
    |
    */
    'fingerprint_storable_class' => \Ninja\DeviceTracker\ValueObject\Fingerprint::class,

    /*
    |--------------------------------------------------------------------------
    | Use redirects to routes or json responses
    |--------------------------------------------------------------------------
    | This option specifies if middleware should redirect to pages or return json
    |
    */
    'use_redirects' => true,

    /*
    |--------------------------------------------------------------------------
    | Load routes
    |--------------------------------------------------------------------------
    | This option specifies if provider should load the routes
    |
    */
    'load_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Regenerate devices
    |--------------------------------------------------------------------------
    | This option specifies if missing devices should be regenerated. Useful to avoid errors
    | when the device is not found in the database, but it is in the cookie.
    */
    'regenerate_devices' => false,

    /*
    |--------------------------------------------------------------------------
    | Allow unknown devices
    |--------------------------------------------------------------------------
    | This option specifies if the system should allow unknown devices to be created.
    | An unknown device is a device that has no information about the browser, platform, etc.
    |
    */
    'allow_unknown_devices' => false,

    /*
    |--------------------------------------------------------------------------
    | Allow bot devices
    |--------------------------------------------------------------------------
    | This option specifies if the system should allow bot devices to be created.
    | A bot device is a device detected as bot, crawler, or spider.
    |
    */
    'allow_bot_devices' => false,

    /*
    |--------------------------------------------------------------------------
    | Middleware configuration
    |--------------------------------------------------------------------------
    |
    */
    'middlewares' => [
        'device-tracker' => [
            /*
            |--------------------------------------------------------------------------
            | Device Tracker Middleware exception on invalid devices
            |--------------------------------------------------------------------------
            | This option specifies how the device tracker middleware should respond when an unknown/invalid/bot device
            | is encountered and the `allow_unknown_device` or the `allow_bot_device` option respectively are set to
            | false.
            | By default the middleware will abort with a 403 - Forbidden - Unknown device detected
            | When true the Ninja\DeviceTracker\Exception\UnknownDeviceDetectedException is thrown.
            |
            */
            'exception_on_invalid_devices' => false,

            /*
            |--------------------------------------------------------------------------
            | Device Tracker Middleware invalid devices http code
            |--------------------------------------------------------------------------
            | This option specifies the error code that should generate when an unknown/invalid/bot device is
            | encountered and the `exception_on_invalid_devices` option is set to `false`.
            | By default the middleware will abort with a 403 - Forbidden - Unknown device detected
            |
            */
            'http_error_code' => 403,
        ],
        'device-checker' => [
            /*
            |--------------------------------------------------------------------------
            | Device Checker Middleware exception on unavailable devices
            |--------------------------------------------------------------------------
            | This option specifies how the device checker middleware should respond when an undefined device is encountered.
            | By default the middleware will abort with a 403 - Forbidden - Device not found.
            | When true the Ninja\DeviceTracker\Exception\DeviceNotFoundException is thrown.
            |
            */
            'exception_on_unavailable_devices' => false,

            /*
            |--------------------------------------------------------------------------
            | Device Checker Middleware invalid devices http code
            |--------------------------------------------------------------------------
            | This option specifies the error code that should generate when an unknown/invalid/bot device is
            | encountered and the `exception_on_invalid_devices` option is set to `false`.
            | By default the middleware will abort with a 403 - Forbidden - Unknown device detected
            |
            */
            'http_error_code' => 403,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Agent Whitelist
    |--------------------------------------------------------------------------
    | This option specifies the allowed bot/unknown user agents that can
    | create devices even with allow_bot_devices set to false
    |
    */
    'user_agent_whitelist' => [],

    /*
    |--------------------------------------------------------------------------
    | Allows multiple sessions per device
    |--------------------------------------------------------------------------
    | This option specifies if the user can have multiple active sessions per device
    |
    */
    'allow_device_multi_session' => false,

    /*
    |--------------------------------------------------------------------------
    | Start new session on login
    |--------------------------------------------------------------------------
    | This option specifies if the user should start a new session when he logs in from a device
    | or if he should continue refreshing the current session.
    |
    */
    'start_new_session_on_login' => false,

    /*
    |--------------------------------------------------------------------------
    | Track guest sessions
    |--------------------------------------------------------------------------
    | This option specifies if the system should track guest sessions
    | or if it should ignore them.
    |
    */
    'track_guest_sessions' => false,

    /*
    |--------------------------------------------------------------------------
    | Session IP change behaviour
    |--------------------------------------------------------------------------
    | This option specifies how should the session change when the IP change
    | when using the same session id.
    |
    | Possible values: 'relocate', 'start_new', 'none'
    |
    */
    'session_ip_change_behaviour' => 'relocate',

    /*
    |--------------------------------------------------------------------------
    | Finished session behaviour
    |--------------------------------------------------------------------------
    | This option specifies what should happen when a finished session is used.
    |
    | Possible values: 'start_new', 'logout'
    | Default: 'logout'
    |
    */
    'finished_session_behaviour' => 'logout',

    /*
    |--------------------------------------------------------------------------
    | Ignore routes for restarting the session
    |--------------------------------------------------------------------------
    | This option specifies the routes which the session must not be restarted
    | when they are requested,(e.g. poller requests and chat requests)
    |
    | The format is:
    | 'ignore_restart' => [
    |   array('method'=>'get', 'route'=>'route.name'),
    |   array('method'=>'post','route'=>'route/uri/{param}')
    | ],
    |
    */
    'ignore_restart' => [],

    /*
    |--------------------------------------------------------------------------
    | Session inactivity seconds
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the period of time in seconds that
    | the session is considered inactive or idle. Set to zero to disable this feature.
    |
    */
    'inactivity_seconds' => 1200,

    /*
    |--------------------------------------------------------------------------
    | Session inactivity behavior
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the behavior when the period of time
    | that the session is considered inactive or idle is reached.
    |
    | Options: 'terminate', 'ignore'
    |
    */
    'inactivity_session_behaviour' => 'terminate',

    /*
    |--------------------------------------------------------------------------
    | Orphaned devices retention period
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the period of time in seconds that the devices without
    | sessions are stored. After this period, the devices are deleted from the database.
    */
    'orphan_retention_period' => 86400, // 1 day

    /*
    |--------------------------------------------------------------------------
    | Enable fingerprinting
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable client-side device fingerprinting.
    |
    */
    'fingerprinting_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Cookie name for current fingerprint device tracking
    |--------------------------------------------------------------------------
    | This option specifies the name of the cookie that will be used to store
    | the client-side fingerprint of the current device.
    |
    */
    'fingerprint_id_cookie_name' => 'laravel_device_fingerprint',

    /*
    |--------------------------------------------------------------------------
    | Fingerprint client library
    |--------------------------------------------------------------------------
    | This option specifies the library that will be used to generate
    | the client-side fingerprint of the current device.
    |
    | Options: 'fingerprintjs', 'clientjs', 'creepjs', 'none'
    |
    */
    'fingerprint_client_library' => 'fingerprintjs',

    /*
    |--------------------------------------------------------------------------
    | Fingerprint transport
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify the transport method for the fingerprint.
    | Options: 'cookie', 'header', 'query'
    |
    */
    'client_fingerprint_transport' => 'cookie',

    /*
    |--------------------------------------------------------------------------
    | Fingerprint key
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the key that will be used to store
    | the fingerprint in cookie/header set by the client.
    |
    */
    'client_fingerprint_key' => 'csf',

    /*
    |--------------------------------------------------------------------------
    | Enable event tracking
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable event tracking. Events are
    | stored in the database and can be used to track user behavior and analyze risks.
    |
    */
    'event_tracking_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Event retention period
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the period of time in days that the events are
    | stored. After this period, the events are deleted from the database.
    |
    */
    'event_retention_period' => 30,

    /*
    |--------------------------------------------------------------------------
    | Location provider
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the location providers that will be used
    | to get the location of the device. The first provider that returns a location will be used.
    |
    | Options: 'ipinfo', 'maxmind'
    |
    */
    'location_providers' => [
        \Ninja\DeviceTracker\Modules\Location\IpinfoLocationProvider::class,
        \Ninja\DeviceTracker\Modules\Location\MaxmindLocationProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Enable cache
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the cache for the device, location, session
    | and user agent.
    |
    | Options: 'device', 'location', 'session', 'ua'
    |
    */
    'cache_enabled_for' => ['device', 'location', 'session', 'ua', 'event_type'],

    /*
    |--------------------------------------------------------------------------
    | Cache store
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the cache driver that should be used
    | for caching
    |
    */
    'cache_store' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the time in seconds that the cache
    | should be stored.
    |
    */
    'cache_ttl' => [
        \Ninja\DeviceTracker\Cache\SessionCache::KEY_PREFIX => 3600,
        \Ninja\DeviceTracker\Cache\DeviceCache::KEY_PREFIX => 3600,
        \Ninja\DeviceTracker\Cache\LocationCache::KEY_PREFIX => 2592000,
        \Ninja\DeviceTracker\Cache\UserAgentCache::KEY_PREFIX => 2592000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Login route name
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the route name for login form.
    | Session Tracker uses this config item to redirect the request to login page.
    | Note: Your login route must have a name.
    |
    */
    'login_route_name' => 'app.login',

    /*
    |--------------------------------------------------------------------------
    | Logout route name
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the route name for logout form.
    | Session Tracker uses this config item to redirect the request to login page.
    | Note: Your logout route must have a name.
    |
    */
    'logout_route_name' => 'app.logout',

    /*
    |--------------------------------------------------------------------------
    | Auth guard
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the guard that should be used for
    | authenticating the user.
    */
    'auth_guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Auth middleware
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the middleware that should be used for
    | authenticating the user in the routes.
    */
    'auth_middleware' => 'auth',

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Enabled
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily enable or disable the Google 2FA feature.
    |
    |
    */
    'google_2fa_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Window
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the window of time in seconds that
    | the security code of the Google 2FA is considered valid. Value: window * 30 seconds
    |
    */
    'google_2fa_window' => 1,

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Company
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the company name that will be used when
    | generating QR image for Google 2FA.
    |
    */
    'google_2fa_company' => 'diego.ninja',

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Route
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the route name for Google 2FA form.
    | This route should show the form to enter the Google 2FA code and QR code.
    */
    'google_2fa_route_name' => 'app.2fa',

    /*
    |--------------------------------------------------------------------------
    | Google 2FA QR Format
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the format of the QR image for Google 2FA.
    | Options: 'base64', 'svg'
    */
    'google_2fa_qr_format' => 'base64',

    /*
    |--------------------------------------------------------------------------
    | Authenticatable class
    |--------------------------------------------------------------------------
    | The class name of the authenticatable model.
    | This option specifies the model class that should be used for authentication.
    | Typically, this is the User model, but it can be customized to any model that
    | implements the Authenticatable contract.
    |
    */
    'authenticatable_class' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | Authenticatable reference table
    |--------------------------------------------------------------------------
    | The class name of the authenticatable model.
    | This option specifies the model class that should be used for authentication.
    | Typically, this is the User model, but it can be customized to any model that
    | implements the Authenticatable contract.
    |
    */
    'authenticatable_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Logout HTTP Code
    |--------------------------------------------------------------------------
    | The http code that will be returned when the user is logged out.
    |
    */
    'logout_http_code' => 403,

    /*
    |--------------------------------------------------------------------------
    | Lock HTTP Code
    |--------------------------------------------------------------------------
    | The http code that will be returned when a session is locked.
    |
    */
    'lock_http_code' => 423,

    /*
    |--------------------------------------------------------------------------
    | Development IP Pool
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the IP addresses that will be
    | used to test the package in development mode.
    |
    */
    'development_ip_pool' => ['138.100.56.25', '2.153.101.169', '104.26.14.39', '104.26.3.12'],

    /*
    |--------------------------------------------------------------------------
    | Development User Agent Pool
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the User Agents that will be
    | used to test the package in development mode.
    |
    */
    'development_ua_pool' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
        'Mozilla/5.0 (iPad; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Version/15.0 Safari/605.1.15 AlohaBrowser/3.2.6',
        'Mozilla/5.0 (Wayland; Fedora Linux 38.20230825.0 Kinoite x86_64; rv:6.4.11-200.fc38.x86_64) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15',
        'Mozilla/5.0 (Wayland; Linux x86_64; System76 Galago Pro (galp2)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Ubuntu/24.04 Edg/122.0.2365.92',
        'Mozilla/7.0 (iPhone; CPU iPhone OS 18_7; iPhone 15 Pro Max) AppleWebKit/533.2 (KHTML, seperti Gecko) CriOS/432.0.8702.51 Seluler/15E148 Safari/804.17',
        'Mozilla/7.0 (iPhone; CPU iPhone OS 18_7; iPhone 15 Pro Max) AppleWebKit/533.2 (KHTML, like Gecko) CriOS/432.0.8702.51 Mobile/15E148 Safari/804.17',
        'Mozilla/5.0 (Linux; Android 13; 2211133G Build/TKQ1.220905.001; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/113.0.5672.76 Mobile Safari/537.36',
        'Mozilla/5.0 (Linux; U; Android 13; pl-pl; Xiaomi 13 Build/TKQ1.220905.001) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/100.0.4896.127 Mobile Safari/537.36 XiaoMi/MiuiBrowser/13.28.0-gn',
    ],

    /*
    |--------------------------------------------------------------------------
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | This options allows to configure the package performances
    |
    */
    'performance' => [
        'session' => [
            /*
            |--------------------------------------------------------------------------
            | Always Sync Last Activity
            |--------------------------------------------------------------------------
            |
            | This option makes the last_activity_at field always update on each request.
            | When disabled, the last_activity_at field is updated only when a request
            | is received after at least `last_activity_update_interval` seconds.
            |
            */
            'always_sync_last_activity' => true,

            /*
            |--------------------------------------------------------------------------
            | Last Activity Update Interval
            |--------------------------------------------------------------------------
            |
            | This option sets the minimum amount of seconds to wait between each
            | `last_activity_at` session field update when the `always_sync_last_activity`
            | is false. This is useful when trying to limit the number of queries that
            | are run by this package.
            |
            */
            'last_activity_update_interval' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Identifiers Parameters
    |--------------------------------------------------------------------------
    |
    | This option allows you to change the searched parameter for each device
    | identifier.
    |
    */
    'device_identifiers_parameters' => [
        /*
         * The advertising id is a unique id assigned to a device used to identify the device
         * without exposing any of the sensible device information. This is usually a resettable
         * id that is unique depending on the platform that is used.
         */
        'advertising_id' => 'advertiser_id',

        /*
         * The device id is a unique identifier of a device. E.g. the IMEI. This can
         * identify univocally a device together with the detected platform
         */
        'device_id' => 'device_id',

        /*
         * The client fingerprint is a pseudo-unique id. This id should be generated by
         * the client using all the possible info that make a device or browser unique.
         * Still, due to privacy concerns, clients are not always able to access proper
         * unique info about the device so the generated fingerprint in some cases can
         * be shared by multiple devices. When trying to identify the device by the
         * provided client fingerprint all other device info is checked to make sure
         * the device is actually the correct one.
         */
        'client_fingerprint' => 'client_fingerprint',
    ],
];
