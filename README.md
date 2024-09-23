# Laravel Device Tracker
This package provides session tracking functionalities, multi session management and user device management features for laravel applications.


## Features
* Session Management
* Multiple session for users
* User Devices
* Locking sessions
* Device verifying
* Device hijacking protection
* Security code for session locking
* Session location tracking
* Ready to use middleware, routes, controllers and resources

## Installation
In composer.json:

    "require": {
        "diego-ninja/laravel-device-tracker": "^1"
    }

Run:

    composer update

Publish config and migrations:

    php artisan vendor:publish --provider="Ninja\DeviceTracker\DeviceTrackerServiceProvider"

Add the service provider to `bootstrap/providers.php` under `providers`:

    return [
        ...
        Ninja\DeviceTracker\DeviceTrackerServiceProvider::class,
    ]

	
Update config file to reference your login and logout route names:

	config/devices.php

Migrate your database:

    php artisan migrate

Add the trait to your user model:

    use Ninja\DeviceTracker\Traits\DeviceTrackerUserTrait;
    
    class User extends Model {
    	use DeviceTrackerUserTrait;
    }


Add the DeviceCheck middleware in your kernel.php file:

    protected $middleware = [
    		'Ninja\DeviceTracker\Middleware\DeviceCheck',
    	];


In Your routes.php file you should add 'session' middleware for routes which you want to keep track of:

    Route::group(['middleware'=>'session'], function(){
        Route::get('your-route', 'YourController@yourAction');
    });

## Usage

WIP
    

## Author

- [Diego Rin Martín](https://github.com/diego-ninja)

