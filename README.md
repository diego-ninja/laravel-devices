# 📱🖥️ Laravel Devices
This package provides session tracking functionalities, multi session management and user device management features for laravel applications.


## ❤️ Features
* Authenticated User Devices
* Session Management
  * Session blocking
  * Google 2FA support
  * Session location tracking
* Device verifying
* Device hijacking detection (WIP)
* Application events
* Ready to use middleware, routes, controllers and dtos and resources

## 📦 Installation
In composer.json:

    "require": {
        "diego-ninja/laravel-devices": "^1"
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

	
Update config file to fit your needs:

	config/devices.php

Migrate your database:

    php artisan migrate

Add the trait to your user model:

    use Ninja\DeviceTracker\Traits\HasDevices;
    
    class User extends Model {
    	use HasDevices;
    }


Add the DeviceTrack middleware in your bootstrap/app.php file. This middleware will track the user device, it will check the presence of a cookie with a device uuid and will create a new device uuid if it doesn't exist.

    protected $middleware = [
    		'Ninja\DeviceTracker\Http\Middleware\DeviceTrack',
    	];


In your routes.php file you should add 'session-tracker' middleware for routes which you want to keep track of. This
middleware will check if the user has a valid session and device. If not, it will redirect to the login page or return a 401 json response depending on your configuration.

    Route::group(['middleware'=>'session-tracker'], function(){
        Route::get('your-route', 'YourController@yourAction');
    });

## Usage

WIP
    

## 🥷🏻 Author

- [Diego Rin Martín](https://github.com/diego-ninja)

