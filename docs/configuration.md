# Configuration Guide

## Overview

Laravel Devices provides extensive configuration options through the `config/devices.php` file. This guide covers all available options and their implications.

## Configuration File Structure

```php
return [
    // Device Configuration
    'device_id_cookie_name' => 'device_id',
    'device_id_storable_class' => DeviceId::class,
    'regenerate_devices' => false,
    
    // Session Configuration
    'session_id_storable_class' => SessionId::class,
    'allow_device_multi_session' => true,
    'start_new_session_on_login' => false,
    
    // Fingerprinting Configuration
    'fingerprinting_enabled' => true,
    'client_fingerprint_transport' => 'header',
    'client_fingerprint_key' => 'X-Device-Fingerprint',
    
    // Session Behavior
    'inactivity_seconds' => 1200,
    'inactivity_session_behaviour' => 'terminate',
    
    // Cache Configuration
    'cache_enabled_for' => ['device', 'location', 'session', 'ua'],
    'cache_store' => 'redis',
    'cache_ttl' => [...],
    
    // Authentication & 2FA
    'google_2fa_enabled' => true,
    'google_2fa_window' => 1,
    'google_2fa_qr_format' => 'base64',
    
    // Routes & Redirects
    'use_redirects' => true,
    'load_routes' => true,
    'auth_guard' => 'web',
    'auth_middleware' => 'auth',
];
```

## Device Configuration

### Device Identification
```php
'device_id_cookie_name' => 'device_id'
```
- The name of the cookie used to store the device identifier
- Default: `'device_id'`
- Recommendation: Change if it conflicts with existing cookies

```php
'device_id_storable_class' => DeviceId::class
```
- Class implementing `StorableId` interface for device identification
- Default: `Ninja\DeviceTracker\ValueObject\DeviceId`
- See: [Custom ID Implementation](custom-ids.md)

### Device Regeneration
```php
'regenerate_devices' => false
```
- Controls device regeneration behavior
- When `true`, missing devices with valid cookies will be regenerated
- When `false`, missing devices throw `DeviceNotFoundException`
- Recommended: `false` for production environments

## Session Configuration

### Session Identification
```php
'session_id_storable_class' => SessionId::class
```
- Class implementing `StorableId` interface for session identification
- Default: `Ninja\DeviceTracker\ValueObject\SessionId`

### Multiple Sessions
```php
'allow_device_multi_session' => true
```
- Controls whether a device can have multiple active sessions
- When `false`, new sessions terminate existing ones
- Example use case:

```php
// When allow_device_multi_session is false
$device = DeviceManager::current();

// First login - Creates session A
SessionManager::start(); // Session A active

// Second login - Session A is terminated, Session B created
SessionManager::start(); // Only Session B active
```

### Session Start Behavior
```php
'start_new_session_on_login' => false
```
- Controls session behavior on login
- When `true`, always creates new session
- When `false`, refreshes existing session if available

## Fingerprinting Configuration

### Basic Setup
```php
'fingerprinting_enabled' => true
```
- Master switch for fingerprinting functionality
- Affects middleware behavior and device tracking

### Transport Method
```php
'client_fingerprint_transport' => 'header'
'client_fingerprint_key' => 'X-Device-Fingerprint'
```
- Controls how fingerprint is transmitted
- Options: `'header'` or `'cookie'`
- Example with header:
```javascript
// Frontend
fetch('/api/endpoint', {
    headers: {
        'X-Device-Fingerprint': 'generated-fingerprint-value'
    }
});
```
- Example with cookie:
```javascript
// Frontend
document.cookie = 'X-Device-Fingerprint=generated-fingerprint-value';
```

## Session Behavior

### Inactivity Settings
```php
'inactivity_seconds' => 1200
```
- Time in seconds before a session is considered inactive
- Set to `0` to disable inactivity checking

```php
'inactivity_session_behaviour' => 'terminate'
```
- Controls what happens to inactive sessions
- Options: `'terminate'` or `'ignore'`
- Behavior example:

```php
// With 'terminate':
if ($session->inactive()) {
    $session->end(); // Session is terminated
    // User needs to login again
}

// With 'ignore':
if ($session->inactive()) {
    // Session marked inactive but still valid
    // Can be reactivated by user activity
    $session->renew();
}
```

## Cache Configuration

### Cache Control
```php
'cache_enabled_for' => ['device', 'location', 'session', 'ua']
```
- Controls which entities use caching
- Can enable/disable individually

### Cache Store
```php
'cache_store' => 'redis'
```
- Specifies cache driver to use
- Recommended: `'redis'` for production
- Options: Any Laravel cache driver

### Cache TTL
```php
'cache_ttl' => [
    'session' => 3600,        // 1 hour
    'device' => 3600,         // 1 hour
    'location' => 2592000,    // 30 days
    'ua' => 2592000,         // 30 days
]
```
- Controls cache duration per entity type
- Values in seconds

## Google 2FA Configuration

### Basic Setup
```php
'google_2fa_enabled' => true
```
- Master switch for Google 2FA functionality
- Affects session locking behavior

### Authentication Window
```php
'google_2fa_window' => 1
```
- Number of intervals to check before/after current timestamp
- Higher values are more lenient but less secure

### QR Code Format
```php
'google_2fa_qr_format' => 'base64'
```
- Format for 2FA QR codes
- Options: `'base64'` or `'svg'`

## Route Configuration

### Redirect Behavior
```php
'use_redirects' => true
```
- Controls middleware response type
- `true`: Uses redirects for web routes
- `false`: Returns JSON responses

### Route Loading
```php
'load_routes' => true
```
- Controls automatic route registration
- Set `false` to define routes manually

### Authentication
```php
'auth_guard' => 'web'
'auth_middleware' => 'auth'
```
- Specifies authentication guard and middleware
- Used by package routes and controllers

## Development Configuration

```php
'development_ip_pool' => [
    '138.100.56.25',
    '2.153.101.169',
    // ...
],
'development_ua_pool' => [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...',
    // ...
]
```
- Test data for development environment
- Used when `app()->environment('local')`

## Configuration Examples

### High Security Setup
```php
return [
    'regenerate_devices' => false,
    'allow_device_multi_session' => false,
    'inactivity_seconds' => 600,
    'inactivity_session_behaviour' => 'terminate',
    'google_2fa_enabled' => true,
    'google_2fa_window' => 1,
    'fingerprinting_enabled' => true,
];
```

### Development Setup
```php
return [
    'regenerate_devices' => true,
    'allow_device_multi_session' => true,
    'inactivity_seconds' => 0,
    'google_2fa_enabled' => false,
    'fingerprinting_enabled' => true,
    'cache_store' => 'array',
];
```

### API-Focused Setup
```php
return [
    'use_redirects' => false,
    'load_routes' => true,
    'auth_guard' => 'api',
    'auth_middleware' => 'auth:api',
    'client_fingerprint_transport' => 'header',
];
```

## Next Steps

- Review [Device Management](device-management.md) for device-specific features
- Learn about [Session Management](session-management.md)
- Set up [Two-Factor Authentication](2fa.md)
- Configure [Device Fingerprinting](fingerprinting.md)