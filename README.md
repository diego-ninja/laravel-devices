# Laravel Device Tracker
This package provides session tracking functionalities, multisession management and user device management features for laravel applications.


## Features
* Session Management
* Session Log
* Multiple session for users
* Request Log
* User Devices


## Installation
In composer.json:

    "require": {
        "diego-ninja/laravel-device-tracker" "1.0.0"
    }

Run:

    composer update
    
Note: For v  5.5 Auto-discovery takes care.

Add the service provider to `bootstrap/providers.php` under `providers`:

    return [
        ...
        Ninja\DeviceTracker\DeviceTrackerServiceProvider::class,
    ]

Add the SessionTracker alias to `config/app.php` under `aliases`:

        'aliases' => [
            'DeviceTracker' => 'Ninja\DeviceTracker\DeviceTrackerFacade',
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

From your user models:

	$user->sessions(); //returns all the sessions of the user form the begining of usage of the package
	
	$user->activeSessions(); //returns all currently active sessions for the user. (User may be logged in with same credentials from different devices)
	
	$user->activeSessions(true); //return all active sessions for the user except the current session.
	
	$user->getFreshestSession(); //Returns the most recent session of the user
	
	$user->devices(); //Returns the collection of users saved trusted devices.
	
	$user->devicesUids(); //Returns array of users saved trusted devices Ids.
	
From SessionTrackerFacade:

	SessionTracker::startSession(); //Creates a new session for current user
	
	SessionTracker::endSession(); //End the current session for the current user
	
	SessionTracker::endSession(true); //End the current session for the current user and forgets the session
	
	SessionTracker::renewSession(); //Restarts the ended session which is not forgotten for the current user. Usefull for restarting the session after locking it for inactivity
	
	SessionTracker::refreshSession($request); //Keeps the session alive for each request. Useful in middleware
	
	SessionTracker::logSession($request); //Logs the current request for the current session. (request logs stored in sessiontracker_session_requests table)
	
	SessionTracker::isSessionInactive(); //Checks if the session is inactive. Determines the inactiveness by subtracting the **delay between last activity and current time** from the **inactivity_seconds** in sessionTracker config file.
	
	SessionTracker::isSessionInactive($user); //Checks if the session for a specific user is inactive. Determines the inactiveness by subtracting the **delay between last activity and current time** from the **inactivity_seconds** in sessionTracker config file.
	
	SessionTracker::blockSession($sessionId); //Blocks (ends and forgets) the current session for the user. (Useful if the user wants to controll all her sessions and block a specific session in a specific location)
	
	SessionTracker::sessionRequests($sessionId); //Returns all requests done by a specific session
	
	SessionTracker::isSessionBlocked(); //Checks if current user does not have an active session
	
	SessionTracker::lockSessionByCode(); //Locks a session by a security code to be unlocked by that code and returns the code. (Usefull for two-step authentication implementation)
	
	SessionTracker::securityCode(); //Returns the security code (hash) for the locked session.
	
	SessionTracker::isSessionLocked(); //Checks if the current session is locked by a security code.
	
	SessionTracker::unlockSessionByCode($code); //Unlocks the locked session by passing the security code. (returns -1 if the code is invalid, -2 if it's expired and 0 if success)
	
	SessionTracker::isUserDevice(); //Returns true if the current device is trusted by the user
	
	SessionTracker::deleteDevice($id); //Deletes a trusted device for the user
	
	SessionTracker::addUserDevice(); //Add the current device as trusted by the user
	
	SessionTracker::forgotSession(); //Checks if the session if forgotten
	
	SessionTracker::sessionId(); //Returns the sessionId for the current session
	
	SessionTracker::deleteSession(); //Deletes and forgets the current session
	
	SessionTracker::refreshSecurityCode(); //Renews the security code by which the current session is locked

## Author

- [Diego Rin Martín](https://github.com/diego-ninja)

