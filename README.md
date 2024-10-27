# 📱🖥️ Laravel Devices

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
[![Total Downloads](https://img.shields.io/packagist/dt/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
![PHP Version](https://img.shields.io/packagist/php-v/diego-ninja/cosmic.svg?style=flat&color=blue)
![Static Badge](https://img.shields.io/badge/laravel-10-blue)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/diego-ninja/laravel-devices?color=blue)
[![Hits-of-Code](https://hitsofcode.com/github/diego-ninja/laravel-devices?branch=main&label=Hits-of-Code)](https://hitsofcode.com/github/diego-ninja/laravel-devices/view?branch=main&label=Hits-of-Code&color=blue)
[![wakatime](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071.svg)](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071)

Laravel Devices is a comprehensive package for managing user devices and sessions in Laravel applications. It provides robust device tracking, session management, and security features including device fingerprinting and two-factor authentication support.

This is a work in progress, and maybe or maybe not be ready for production use.  Help is needed to improve the project and write documentation so if you are interested in contributing, please read the [contributing guide](./docs/contributing.md).

## ❤️ Features

* Authenticated User Devices
* Session Management
  * Session blocking
  * Session locking (Google 2FA support for session locking)
  * Session location tracking
* Device verifying
* Custom id format for sessions and devices
* Application events
* Ready to use middleware, routes, controllers, dtos, value objects and resources
* Ready to use Google 2FA integration
* Cache support for devices, sessions, locations and user agents
* [FingerprintJS](https://github.com/fingerprintjs/fingerprintjs) and [ClientJS](https://github.com/jackspirou/clientjs) integrations for device fingerprinting

## 🗓️ Planned features

* Device hijacking detection
* Livewire integrations for [Laravel Jetstream](https://jetstream.laravel.com/) and [Laravel Breeze](https://laravel.com/docs/11.x/starter-kits#laravel-breeze)
* [Laravel Pulse](https://laravel.com/docs/11.x/pulse) integration


Please refer to the [documentation](./docs/README.md) for more information on the features and how to use this package.

## 📦 Installation

In composer.json:
```json  
"require": { "diego-ninja/laravel-devices": "^1" }  
```

and run:
```bash
composer update  
```

or 
```bash
composer require diego-ninja/laravel-devices
```


Publish config and migrations:
```bash
php artisan vendor:publish --provider="Ninja\DeviceTracker\DeviceTrackerServiceProvider"  
```

## 🚀 Quickstart

Add the service provider to `bootstrap/providers.php` under `providers`:
```php  
return [ 
	...        
	Ninja\DeviceTracker\DeviceTrackerServiceProvider::class,    
]  
```

Update config file to fit your needs:
```php  
config/devices.php  
```

Migrate your database:
```bash  
php artisan migrate  
```

Add the HasDevices trait to your user model:
```php  
use Ninja\DeviceTracker\Traits\HasDevices;        

class User extends Model {  
	use HasDevices;    
	...
}  
```

Add the Has2FA trait to your user model if you want to use the Google 2FA provided integration:
```php  
use Ninja\DeviceTracker\Traits\Has2FA;        

class User extends Model {  
	use Has2FA;    
	...
}  
```  

Add the DeviceTrack middleware in your bootstrap/app.php file. This middleware will track the user device, it will check the presence of a cookie with a device uuid and will create a new device id if it doesn't exist.

```php
protected $middleware = [
	'Ninja\DeviceTracker\Http\Middleware\DeviceTrack',
	...
];
```


In your routes.php file you should add 'session-tracker' middleware for routes which you want to keep track of. This  middleware will check if the user has a valid session and device. If not, it will redirect to the login page or return a 401 json response depending on your configuration.

```php
	Route::group(['middleware'=>'session-tracker'], function(){
		Route::get('your-route', 'YourController@yourAction');    
	});
```

## 🎛️ Configuration

[Config file](https://github.com/diego-ninja/laravel-devices/blob/6e3373936cbe3ba9e9c24c97fa29b8798ec23992/config/devices.php) is fully commented and self-explanatory. Please check detailed instructions on every aspect there.



## 🙏 Credits

This project is developed and maintained by 🥷 [Diego Rin](https://diego.ninja) in his free time.

Special thanks to:

- [Laravel Framework](https://laravel.com/) for providing the most exciting and well-crafted PHP framework.
- [Hamed Mehryar](https://github.com/hamedmehryar) for developing the [inital code](https://github.com/hamedmehryar/laravel-session-tracker) that serves Laravel Devices as starting point.
- All the contributors and testers who have helped to improve this project through their contributions.

If you find this project useful, please consider giving it a ⭐ on GitHub!
