<?php

use Illuminate\Support\Facades\Route;
use Ninja\DeviceTracker\Modules\Fingerprinting\Http\Controllers\FingerprintController;

Route::get('tracking/pixel/{ref}', [FingerprintController::class, 'pixel'])
    ->name('tracking.pixel')
    ->middleware('web');

Route::group([
    'as' => 'device::',
    'prefix' => 'api/devices',
    'middleware' => Config::get('devices.auth_middleware')
], function (): void {
    Route::get('/', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@list')->name('list');
    Route::get('/{id}', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@show')->name('show');
    Route::patch('/{id}/verify', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@verify')->name('verify');
    Route::patch('/{id}/hijack', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@hijack')->name('hijack');
    Route::patch('/{id}/forget', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@forget')->name('forget');
    Route::post('/signout', 'Ninja\DeviceTracker\Http\Controllers\DeviceController@signout')->name('signout');
});

Route::group([
    'as' => 'session::',
    'prefix' => 'api/sessions',
    'middleware' => Config::get('devices.auth_middleware')
], function (): void {
    Route::get('/', 'Ninja\DeviceTracker\Http\Controllers\SessionController@list')->name('list');
    Route::get('/active', 'Ninja\DeviceTracker\Http\Controllers\SessionController@active')->name('active');
    Route::get('/{id}', 'Ninja\DeviceTracker\Http\Controllers\SessionController@show')->name('show');
    Route::patch('/{id}/renew', 'Ninja\DeviceTracker\Http\Controllers\SessionController@renew')->name('renew');
    Route::delete('/{id}/end', 'Ninja\DeviceTracker\Http\Controllers\SessionController@end')->name('end');
    Route::patch('/{id}/block', 'Ninja\DeviceTracker\Http\Controllers\SessionController@block')->name('block');
    Route::patch('/{id}/unblock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@unlbock')->name('unblock');
    Route::post('/signout', 'Ninja\DeviceTracker\Http\Controllers\SessionController@signout')->name('signout');
});

Route::group([
    "as" => "2fa::",
    "prefix" => "api/2fa",
    "middleware" => Config::get("devices.auth_middleware")
], function (): void {
    Route::get("/code", "Ninja\DeviceTracker\Http\Controllers\TwoFactorController@code")
        ->withoutMiddleware(['session-tracker'])
        ->name("code");
    Route::post("/verify", "Ninja\DeviceTracker\Http\Controllers\TwoFactorController@verify")
        ->withoutMiddleware(['session-tracker'])
        ->name("verify");
    Route::patch("/disable", "Ninja\DeviceTracker\Http\Controllers\TwoFactorController@disable")
        ->withoutMiddleware(['session-tracker'])
        ->name("disable");
    Route::patch("/enable", "Ninja\DeviceTracker\Http\Controllers\TwoFactorController@enable")
        ->withoutMiddleware(['session-tracker'])
        ->name("enable");
});
