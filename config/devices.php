<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cookie name for current user deice tracking
    |--------------------------------------------------------------------------
    | This option specifies the name of the cookie that will be used to store
    | the device id of the current user.
    |
    */
    'device_id_cookie_name' => 'device_id',

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
    | Allows multiple sessions per device
    |--------------------------------------------------------------------------
    | This option specifies if the user can have multiple active sessions per device
    |
    */
    'allow_device_multi_session' => true,

    /*
    |--------------------------------------------------------------------------
    | Ignore routes for restarting the session
    |--------------------------------------------------------------------------
    | This option specifies the routes which the session must not be restarted
    | when they are requested,(e.g. poller requests and chat requests)
    |
    | The format is:
    | 'ignore_refresh' => [
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
    'inactivity_seconds' => 0,

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
    | Google 2FA Email
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the email that will be used when
    | generating QR image for Google 2FA.
    |
    */
    'google_2fa_email' => 'yosoy@diego.ninja',

    /*
    |--------------------------------------------------------------------------
    | Google 2FA Company
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the company name that will be used when
    | generating QR image for Google 2FA.
    |
    */
    'google_2fa_company' => 'diego.ninja', // 30 seconds

    /*
    |--------------------------------------------------------------------------
    | Security Code lifetime seconds
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the period of time in seconds that
    | the security code of the sessions is considered as expired. Set to zero to disable this feature.
    |
    */
    'security_code_lifetime' => 1200,

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
    | Development IP Pool
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the IP addresses that will be
    | used to test the package in development mode.
    |
    */
    'development_ip_pool' => ['138.100.56.25','2.153.101.169','104.26.14.39','104.26.3.12'],

];
