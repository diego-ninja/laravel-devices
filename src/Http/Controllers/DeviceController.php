<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Http\Resources\DeviceResource;

final class DeviceController extends Controller
{
    public function list(Request $request)
    {
        $devices = $request
            ->user(Config::get('devices.auth_guard'))->devices;

        return response()->json(DeviceResource::collection($devices)->with($request));
    }

    public function show(Request $request)
    {
        $device = $request
            ->user(Config::get('devices.auth_guard'))
            ->devices()
            ->with('sessions')
            ->find($request->input('id'));

        if ($device) {
            return response()->json(DeviceResource::make($device)->with($request));
        }

        return response()->json(['message' => 'Device not found'], 404);
    }
}
