<?php

return [

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
    | the session is considered inactive or idle.
    |
    */

    'inactivity_seconds' => 1200,

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
    | Login Code route name
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the route name for login code page.
    | Session Tracker uses this config item to redirect the request to login page.
    | Note: Your login code route must have a name.
    |
    */

    'security_code_route_name' => 'app.securityCode',


    /*
    |--------------------------------------------------------------------------
    | Security Code lifetime seconds
    |--------------------------------------------------------------------------
    |
    | This option allows you to easily specify the period of time in seconds that
    | the security code of the sessions is considered as expired.
    |
    */
    'security_code_lifetime' => 1200,

];
