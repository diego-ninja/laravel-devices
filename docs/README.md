# Laravel Devices Documentation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
[![Total Downloads](https://img.shields.io/packagist/dt/diego-ninja/laravel-devices.svg?style=flat&color=blue)](https://packagist.org/packages/diego-ninja/laravel-devices)
![PHP Version](https://img.shields.io/packagist/php-v/diego-ninja/cosmic.svg?style=flat&color=blue)
![Static Badge](https://img.shields.io/badge/laravel-10-blue)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/diego-ninja/laravel-devices?color=blue)
[![Hits-of-Code](https://hitsofcode.com/github/diego-ninja/laravel-devices?branch=main&label=Hits-of-Code)](https://hitsofcode.com/github/diego-ninja/laravel-devices/view?branch=main&label=Hits-of-Code&color=blue)
[![wakatime](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071.svg)](https://wakatime.com/badge/user/bd65f055-c9f3-4f73-92aa-3c9810f70cc3/project/94491bff-6b6c-4b9d-a5fd-5568319d3071)

## Introduction
Laravel Devices is a comprehensive package for managing user devices and sessions in Laravel applications. It provides robust device tracking, session management, and security features including device fingerprinting and two-factor authentication support.

## Documentation
This documentation has been generated almost in its entirety using 🦠 [Claude 3.5 Sonnet](https://claude.ai/) based on source code analysis. Some sections may be incomplete, outdated or may contain documentation for planned or not-released features. For the most accurate information, please refer to the source code or open an issue on the package repository.

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

## Credits
This package is developed and maintained by [Diego Rin](https://diego.ninja).

## License
The MIT License (MIT). Please see [License File](../LICENSE) for more information.