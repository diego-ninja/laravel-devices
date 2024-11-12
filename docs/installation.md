# Installation Guide

## Requirements

Before installing Laravel Devices, ensure your environment meets the following requirements:

- PHP 8.2 or higher
- Laravel 10.x or 11.x
- Redis extension (recommended for caching)
- Composer

## Step-by-Step Installation

### 1. Install via Composer

```bash
composer require diego-ninja/laravel-devices
```

### 2. Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="Ninja\DeviceTracker\DeviceTrackerServiceProvider"
```

This command will publish:
- Configuration file: `config/devices.php`
- Database migrations in `database/migrations/`:
    - Device table migration
    - Sessions table migration
    - Google 2FA configuration table migration
    - User devices pivot table migration
- Blade templates for fingerprint library injection

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Configure User Model

Add the necessary traits to your User model:

```php
use Ninja\DeviceTracker\Traits\HasDevices;
use Ninja\DeviceTracker\Traits\Has2FA; // Optional, only if using 2FA

class User extends Authenticatable
{
    use HasDevices;
    use Has2FA; // Optional
    
    // ... rest of your model
}
```

### 5. Register Middleware

Add the required middleware in your `boot/app.php`:

```php
protected $middleware = [
    // ... other middleware
    \Ninja\DeviceTracker\Http\Middleware\DeviceTracker::class,
    \Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware\FingerprintTracker::class,
];

protected $routeMiddleware = [
    // ... other route middleware
    'session-tracker' => \Ninja\DeviceTracker\Http\Middleware\SessionTracker::class,
];
```

### 6. Configure Service Provider (Optional)

If you're using Laravel < 10, add the service provider to `config/app.php`:

```php
'providers' => [
    // ... other providers
    Ninja\DeviceTracker\DeviceTrackerServiceProvider::class,
],
```

For Laravel 10+ using package discovery, this step is not necessary.

### 7. Configure Cache Driver (Recommended)

For optimal performance, configure a fast cache driver in your `.env` file:

```env
CACHE_DRIVER=redis
REDIS_CLIENT=predis
```

## Post-Installation Steps

### 1. Configure Google 2FA (Optional)

If you plan to use Google 2FA:

1. Make sure your User model uses the `Has2FA` trait
2. Enable 2FA in the configuration:

```php
// config/devices.php
return [
    'google_2fa_enabled' => true,
    'google_2fa_company' => env('APP_NAME', 'Your Company'),
];
```

### 2. Configure Device Fingerprinting (Optional)

Choose your preferred fingerprinting library:

```php
// config/devices.php
return [
    'fingerprinting_enabled' => true,
    'client_fingerprint_transport' => 'header', // or 'cookie'
    'client_fingerprint_key' => 'X-Device-Fingerprint',
];
```

### 3. Configure Session Handling

Define your preferred session handling behavior:

```php
// config/devices.php
return [
    'allow_device_multi_session' => true,
    'start_new_session_on_login' => false,
    'inactivity_seconds' => 1200, // 20 minutes
    'inactivity_session_behaviour' => 'terminate', // or 'ignore'
];
```

## Verify Installation

To verify your installation is working correctly:

1. Check migrations are applied:
```bash
php artisan migrate:status
```

2. Test device tracking is working:
```php
use Ninja\DeviceTracker\Facades\DeviceManager;

// In a route or controller:
if (DeviceManager::tracked()) {
    $device = DeviceManager::current();
    return "Device tracked successfully: " . $device->uuid;
}
```

## Troubleshooting

### Common Issues

1. **Class not found errors**
    - Run `composer dump-autoload`
    - Ensure provider is registered

2. **Migration errors**
    - Check database permissions
    - Ensure migrations are published
    - Clear cache: `php artisan config:clear`

3. **Middleware not working**
    - Verify middleware registration in Kernel.php
    - Check middleware order
    - Clear route cache: `php artisan route:clear`

### Debug Mode

Enable debug mode in your configuration for more detailed error messages:

```php
// config/devices.php
return [
    'debug' => true,
    // ... other config
];
```

## Next Steps

Once installed, you should:

1. Review the [Configuration Guide](configuration.md) for detailed setup options
2. Follow the [Quick Start Guide](quick-start.md) for basic usage
3. Set up [Device Fingerprinting](fingerprinting.md) if needed
4. Configure [Two-Factor Authentication](2fa.md) if required

For more information on specific features:
- [Device Management](device-management.md)
- [Session Management](session-management.md)
- [API Reference](api-reference.md)