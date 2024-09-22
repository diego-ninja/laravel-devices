<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Http\Resources\DeviceResource;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @authenticated
 */
final class DeviceController extends Controller
{
    public function list(Request $request)
    {
        $devices = $request
            ->user(Config::get('devices.auth_guard'))->devices;

        return response()->json(DeviceResource::collection($devices));
    }

    public function show(Request $request, string $id)
    {
        $device = $request
            ->user(Config::get('devices.auth_guard'))
            ->devices()
            ->with('sessions')
            ->where('uuid', Uuid::fromString($id))
            ->first();

        if ($device) {
            return response()->json(DeviceResource::make($device));
        }

        return response()->json(['message' => 'Device not found'], 404);
    }
}
