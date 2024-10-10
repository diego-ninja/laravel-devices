# 📱🖥️ Laravel Devices

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/cosmic)
[![Total Downloads](https://img.shields.io/packagist/dt/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
![PHP Version](https://img.shields.io/packagist/php-v/diego-ninja/cosmic.svg?style=flat&color=blue)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/diego-ninja/laravel-devices?color=blue)
[![Hits-of-Code](https://hitsofcode.com/github/diego-ninja/laravel-devices?branch=main&label=Hits-of-Code)](https://hitsofcode.com/github/diego-ninja/laravel-devices/view?branch=main&label=Hits-of-Code&color=blue)

This package provides session tracking functionalities, multi session management and user device management features for Laravel applications. This package is slightly based on [laravel-session-tracker](https://github.com/hamedmehryar/laravel-session-tracker) but updated with new features, and, important note, intended to use with recent Laravel versions, 10 and 11.

This is a work in progress, and maybe or maybe not be ready for production use.  Help is needed to improve the project, so if you are interested in contributing, please read the [contributing guide](./docs/contributing.md).

## ❤️ Features

* Authenticated User Devices
* Session Management
  * Session blocking
  * Session locking (Google 2FA support for session locking)
  * Session location tracking
* Device verifying
* Device hijacking detection (WIP)
* Custom id format for sessions and devices
* Application events
* Ready to use middleware, routes, controllers, dtos, value objects and resources
* Ready to use Google 2FA integration
* Cache support for devices, sessions, locations and user agents

## 📦 Installation

In composer.json:
```json  
"require": { "diego-ninja/laravel-devices": "^1" }  
```

Run:
```bash 
composer update  
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

## ⚙️ How it works

This package tracks user devices and sessions. It requires a current Laravel session to be active to work properly, so you need to start a session before using it, this is achieved by adding the StartSession middleware before the SessionTracker one.

### 📱 Device management
#### Tracking
When a user accesses a route with DeviceTracker middleware enabled, the middleware checks for the presence of a cookie (devices.device_id_cookie_name), if the cookie is already set, the middleware does nothing, if not, the middleware generates a new configured uuid, sets the cookie and forwards the request. Note that, in this step, there isn't a proper Device model created yet. The DeviceTracked event is fired after setting the cookie to the user.
#### Creating
When a user logs in to the application,  the AuthenticationHandler listening to the Login auth event, checks for the presence of the device uuid, either in application memory if this is the first time the device is tracked, or in the cookie in subsequent requests. If the device uuid is available, it tries to create a new device if it does not already exist, by default this can be changed in the configuration, the new device is flagged as unverified. The DeviceCreated event is fired after this action.
#### Verifying
The initial status for a device is unverified, this means the device has been created but we don't have any valid user interaction from the user verifying the device, by default we don't consider the user login sufficient to verify the device. A device can be verified in two main ways, either by unlocking a session using a 2FA method, or by the user marking the device as verified using the controller action. The DeviceVerified event is fired when a device is flagged as verified.

By default while a device is unverified, all the sessions from that device will be created in a locked state if the 2FA is enabled, both globally and for the logged user, and will be need to unlock to be able to use the api from that session.
#### Hijacking
A device can be flagged as hijacked at any time, now by controller and model, and in the near future with an automatic mechanism (WIP) to detect possible device or session hijacking attempts. This is a work in progress. A hijacked device can no longer create sessions. When a device is flagged as hijacked, the DeviceHijacked event is fired and all sessions associated with the device are blocked. At the moment, the hijacked state is a final state, this mean that flagging a device as hijacked is a non-back operation, there isn't a way to set back the device as verified.

### 🔒 Session management
Every time a user logs in to the application, the AuthenticationHandler, right after creating the device, tries to start a session, it checks if there is an active session already started, if so it renews the session, setting the last_activity_at in the model. If there is no active session for that user and device, it creates a new one. If the device creating the session is verified, the session is created with status active, if the device is flagged as unverified, the session is created with status locked and must be unlocked in a further step. This is the default behavior but can be changed using the configuration file.

The SessionStarted event is dispatched after the session is created.
#### Location tracking
Every time a new session is created, the ip address location is tracked and the geographical information is added to the session. You can develop your own LocationProviders implementing the LocationProvider contract. An IpInfo location provider is configured and used by default.

IP address location resolutions are suitable to be cached. You can configure cache in the [config file](https://github.com/diego-ninja/laravel-devices/blob/6e3373936cbe3ba9e9c24c97fa29b8798ec23992/config/devices.php).

The following information is stored associated to the session, you can use this information to detect hijacking attempts, or at least notify the user about suspicious activity if the session initial location differs a lot from request location.

```json
{
  "ip": "2.153.101.169",
  "hostname": "2.153.101.169.dyn.user.ono.com",
  "country": "ES",
  "region": "Madrid",
  "city": "Madrid",
  "postal": "28004",
  "latitude": "40.4165",
  "longitude": "-3.7026",
  "timezone": "Europe/Madrid",
  "label": "28004 Madrid, Madrid, ES"
}
```

#### Session locking
A session is locked if started from an unverified device, meaning that the user must unlock the session in order to interact with the application. This module provides out-of-the-box integration with Google 2FA authentication, which uses authenticator codes to unlock sessions.

Once a session is unlocked with an authenticator code, the device that owns the session is marked as verified and will no longer ask for 2FA for the sessions it creates. It will create active sessions.

Locked sessions cannot be unlocked using the supplied controller, so if you need another mechanism to artificially unlock sessions, you should implement it.

##### Google 2FA support
This module provides out-of-the-box integration with Google 2FA via Authenticator codes, a controller is provided, this controller needs an authenticated user to work. A successful code verification attempt will unlock the current session and set the underlying device as verified. If you need to change this behavior, you should not use the provided controller and implement your own.

The controller `Ninja\DeviceTracker\Http\Controllers\TwoFactorController` exposes the following actions mapped to their respective routes.

```php
/** GET: Returns the QR code for the user **/
public function code(Request $request): RedirectResponse|JsonResponse;

/** POST: Verifies the code provided in the code body parameter **/
public function verify(Request $request): RedirectResponse|JsonResponse;

/** PATCH: Enables 2FA for the logged user **/
public function enable(Request $request): JsonResponse;

/** PATCH: Disabled 2FA for the logged user **/
public function disable(Request $request): JsonResponse;
```

You can integrate another 2FA solution and use its events to notify this module of successful and failed attempts. You should send a `Ninja\DeviceTracker\Events\Google2FASuccess` with the Authenticatable instance as payload when a successful attempt is made. A listener for this event will be set and the current session and device will be unlocked and verified for the user.

#### Session blocking
A session can be blocked via two mechanism, calling the Sessions controller and manually blocking a suspicious session by the user or, when a device is flagged as hijacked by the application, all their sessions are blocked. When a session is blocked the event SessionBlocked is dispatched. The SessionTracker middleware will return a 401 Unauthorized response when detecting a blocked session.

Blocked sessions only can be set to active again, unblock session, using the Sessions controller.

#### Ending sessions
When a user logs out of the application, the AuthenticationHandler listening on the Logout auth event sets the session status to finished and the finished_at to the current date and time.

If the option to automatically log out inactive sessions is set in the configuration file, the SessionTracker middleware will terminate the current session when it becomes inactive.

The finished state is a final state, this means that finishing a session is a non-return operation, there's no way to return the session to active.

#### Inactive sessions
A session becomes inactive when a number of seconds, defined in the configuration file, has passed without interaction between the logged on user and the application, the session is considered inactive, the inactive status is a calculated status, not a static one, it is calculated using the inactive() method of the Session model.

You can just let the inactive sessions exist without taking any action and wait for an external source to terminate them, the front application or a background process, or use the option in the configuration file to automatically terminate the inactive sessions when the SessionTracker middleware detects them.

## 🙏 Credits

This project is developed and maintained by 🥷 [Diego Rin](https://diego.ninja) in his free time.

Special thanks to:

- [Laravel Framework](https://laravel.com/) for providing the most exciting and well-crafted PHP framework.
- [Hamed Mehryar](https://github.com/hamedmehryar) for developing the [inital code](https://github.com/hamedmehryar/laravel-session-tracker) that serves Laravel Devices as starting point.
- All the contributors and testers who have helped to improve this project through their contributions.

If you find this project useful, please consider giving it a ⭐ on GitHub!
