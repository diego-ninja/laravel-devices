# 🚀 Quickstart

## Service provider
Add the service provider to `bootstrap/providers.php` under `providers`:
```php  
return [ 
	...        
	Ninja\DeviceTracker\DeviceTrackerServiceProvider::class,    
]  
```

## Config file
Update [config file](configuration.md) to fit your needs:
```php  
config/devices.php  
```

## Run migrations
Migrate your [database](database-schema.md) to create the necessary tables:
```bash  
php artisan migrate  
```

## Configure model
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

## Add middlewares:

Add the DeviceTrack middleware in your bootstrap/app.php file. This middleware will track the user device, it will check the presence of a cookie with a device uuid and will create a new device if it doesn't exist. 

Optionally, you can add the FingerprintTracker middleware to try to fingerprint the device. This middleware uses javascript injection to work, so, it only works on html responses. Thi middleware needs a current device to work, so it should be placed after the DeviceTracker middleware.

```php
protected $middleware = [
	'Ninja\DeviceTracker\Http\Middleware\DeviceTracker',
	...
	'Ninja\DeviceTracker\Modules\Fingerprinting\Http\Middleware\FingerprintTracker',
];
```

In your routes.php file you should add 'session-tracker' middleware for routes which you want to keep track of. This  middleware will check if the user has a valid session and device. If not, it will redirect to the login page or return a 401 json response depending on your configuration.

```php
Route::group(['middleware'=>'session-tracker'], function(){
    Route::get('your-route', 'YourController@yourAction');    
});
```

## Next Steps

Once installed, you should:

1. Review the [Configuration Guide](configuration.md) for detailed setup options
2. Set up [Device Fingerprinting](fingerprinting.md) if needed
3. Configure [Two-Factor Authentication](2fa.md) if required

For more information on specific features:
- [Device Management](device-management.md)
- [Session Management](session-management.md)
- [API Reference](api-reference.md)
