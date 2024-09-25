<?php

use Illuminate\Support\Facades\Route;

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
    Route::get('/{id}', 'Ninja\DeviceTracker\Http\Controllers\SessionController@show')->name('show');
    Route::patch('/{id}/renew', 'Ninja\DeviceTracker\Http\Controllers\SessionController@renew')->name('renew');
    Route::delete('/{id}/end', 'Ninja\DeviceTracker\Http\Controllers\SessionController@end')->name('end');
    Route::patch('/{id}/lock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@lock')->name('lock');
    Route::patch('/{id}/unlock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@unlock')->name('unlock');
    Route::patch('/{id}/block', 'Ninja\DeviceTracker\Http\Controllers\SessionController@block')->name('block');
    Route::patch('/{id}/unblock', 'Ninja\DeviceTracker\Http\Controllers\SessionController@unlbock')->name('unblock');
    Route::patch('/{id}/refresh', 'Ninja\DeviceTracker\Http\Controllers\SessionController@refresh')->name('refresh');
    Route::post('/signout', 'Ninja\DeviceTracker\Http\Controllers\SessionController@signout')->name('signout');
});
