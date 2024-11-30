<?php

use Ninja\DeviceTracker\DTO\Device;

include_once 'vendor/autoload.php';

$ua = 'Mozilla/5.0 (Linux; Android 10; SM-A205U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.181 Mobile Safari/537.36';
$detect = new Ninja\DeviceTracker\Modules\Detection\Device\UserAgentDeviceDetector;
$device = $detect->detect($ua);
$data = $device?->array();
dd($data);
$another = Device::from($data);
dd($another);
