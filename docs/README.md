# Laravel Devices Documentation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
[![Total Downloads](https://img.shields.io/packagist/dt/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
![PHP Version](https://img.shields.io/packagist/php-v/diego-ninja/cosmic.svg?style=flat&color=blue)
![Static Badge](https://img.shields.io/badge/laravel-10-blue)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/diego-ninja/laravel-devices?color=blue)
[![Hits-of-Code](https://hitsofcode.com/github/diego-ninja/laravel-devices?branch=main&label=Hits-of-Code)](https://hitsofcode.com/github/diego-ninja/laravel-devices/view?branch=main&label=Hits-of-Code&color=blue)
[![wakatime](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071.svg)](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071)

## Documentation
This documentation has been generated almost in its entirety using 🦠 [Claude 3.5 Sonnet](https://claude.ai/) based on source code analysis. Some sections may be incomplete, outdated or may contain documentation for planned or not-released features. For the most accurate information, please refer to the source code or open an issue on the package repository.

If you find any issues or have suggestions for improvements, please open an issue or a pull request on the package repository.

### Getting Started
* [Installation](installation.md)
* [Quick Start](quick-start.md)
* [Configuration](configuration.md)

### Core Concepts
* [Overview](system-overview.md)  
* [Database Schema](database-schema.md)
* [Device Management](device-management.md)
* [Session Management](session-management.md)

### Features
* [Device Fingerprinting](fingerprinting.md)
* [Two-Factor Authentication](2fa.md)
* [Location Tracking](location-tracking.md)
* [Caching System](caching.md)

### Developer Reference
* [API Reference](api-reference.md)
  * [Devices API](api/devices.md)
  * [Sessions API](api/sessions.md)
  * [2FA API](api/2fa.md)
* [Events](events.md)
* [Custom ID Implementation](custom-ids.md)
* [Extending the Package](extending.md)

## Requirements
- PHP 8.2 or higher
- Laravel 10.x or 11.x
- Redis extension (recommended for caching)

## Quick Installation
```bash
composer require diego-ninja/laravel-devices
```

## Basic Usage

Include in your middleware stack both `DeviceTracker::class` and `SessionTracker::class` in this order or simply use
the managers like this: 

```php
// Track current device
$device = DeviceManager::track();

// Start a new session
$session = SessionManager::start();

// Check device status
if ($device->verified()) {
    // Process request
}
```

## Upgrade 1.2.x to 2.0

The upgrade should be smooth with just a couple of change that need to be taken care of.

Potential breaking changes:

- the `Device` model has lost the `ip` value. The ip is now only available through the `Session` model;
- `user_devices` table has been dropped since it was containing only data duplicated from the `devices` table.
  The relation `HasManyDevices` has been properly updated, using the already available `device_sessions` intermediate table.
  All uses of the `HasManyDevices` relation should not change;
- configuration file has been refactored. Old variable names are still supported. See the [config](../config/devices.php)
  file to check the new namings.

Other important changes:

- model history is now supported through the `history` table, logging column changes of `devices` and `session` tables;
- `fingerprint` is now always active and simply ignored when not available in the request.
- `fingerprint` transport has been refactored and standardized like session and device transports;
- `Device` model identification has improved. The model has been enriched with two new values, that can both be used separately to identify the physical devices 
  proved the platform stays the same:
  - `device_id`: is an anonymous unique device identifier.
  - `advertising_id`: is a unique, user-resettable, and user-deletable ID for advertising - ([Google](https://support.google.com/googleplay/android-developer/answer/6048248?hl=en), [Apple](https://developer.apple.com/documentation/adsupport/asidentifiermanager/advertisingidentifier))

## Credits
This project is developed and maintained by 🥷 [Diego Rin](https://diego.ninja) and [Davide Pizzato](https://github.com/dvdpzzt-kimia) in their free time.

## License
The MIT License (MIT). Please see [License File](../LICENSE) for more information.