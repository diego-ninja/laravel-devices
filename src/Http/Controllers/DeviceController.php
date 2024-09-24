<?php

namespace Ninja\DeviceTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Ninja\DeviceTracker\Http\Resources\DeviceResource;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Session;
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
        $device = $this->getUserDevice($request, $id);

        if ($device) {
            return response()->json(DeviceResource::make($device));
        }

        return response()->json(['message' => 'Device not found'], 404);
    }

    public function verify(Request $request, string $id)
    {
        $device = $this->getUserDevice($request, $id);

        if ($device) {
            $device->verify();
            return response()->json(['message' => 'Device verified successfully']);
        }

        return response()->json(['message' => 'Device not found'], 404);
    }

    public function hijack(Request $request, string $id)
    {
        $device = $this->getUserDevice($request, $id);

        if ($device) {
            $device->hijack();
            return response()->json(['message' => sprintf('Device %s flagged as hijacked', $device->uuid)]);
        }

        return response()->json(['message' => 'Device not found'], 404);
    }

    public function forget(Request $request, string $id)
    {
        $device = $this->getUserDevice($request, $id);

        if ($device) {
            $device->forget();
            return response()->json(['message' => 'Device forgotten successfully. All active sessions were ended.']);
        }

        return response()->json(['message' => 'Device not found'], 404);
    }

    public function signout(Request $request, string $id)
    {
        $device = $this->getUserDevice($request, $id);
        $sessions = $device->activeSessions();
        $sessions->each(fn(Session $session) => $session->end());

        return response()->json(['message' => 'All active sessions for device finished successfully.']);
    }

    private function getUserDevice(Request $request, string $id): ?Device
    {
        return $request
            ->user(Config::get('devices.auth_guard'))
            ->devices()
            ->where('uuid', Uuid::fromString($id))
            ->firstOrFail();
    }
}
