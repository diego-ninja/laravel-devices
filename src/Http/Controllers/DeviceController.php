<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ninja\DeviceTracker\DTO\Device as DeviceDTO;

final class DeviceController extends Controller
{
    public function list(Request $request)
    {
        $ret = [];

        $devices = Auth::user()->devices;
        foreach ($devices as $device) {
            $ret[] = DeviceDTO::fromModel($device);
        }

        return response()->json($ret);
    }

    public function show(Request $request)
    {
        $device = Auth::user()->devices()->find($request->input('id'));

        if ($device) {
            return response()->json(DeviceDTO::fromModel($device));
        }

        return response()->json(['message' => 'Device not found'], 404);
    }
}
