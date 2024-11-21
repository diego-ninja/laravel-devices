<?php

namespace Ninja\DeviceTracker\Livewire\Components;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ninja\DeviceTracker\Models\Device;

class ActiveDevicesPanel extends Component
{
    public Collection $devices;

    public function mount(): void
    {
        $this->refreshDevices();
    }

    public function refreshDevices(): void
    {
        $this->devices = Auth::user()->devices()
            ->with(['sessions' => function ($query) {
                $query->active();
            }])
            ->get();
    }

    public function hijackDevice($uuid): void
    {
        $device = Device::byUuid($uuid);
        $device->hijack(Auth::user());
        $this->refreshDevices();

        $this->dispatch('device-hijacked', deviceId: $uuid);
    }

    public function signoutDevice($uuid): void
    {
        /** @var Device $device */
        $device = Device::byUuid($uuid);
        $device->sessions()->active()->each->end();
        $this->refreshDevices();

        $this->dispatch('device-sessions-ended', deviceId: $uuid);
    }

    public function forgetDevice($uuid)
    {
        $device = Device::byUuid($uuid);
        $device->forget();
        $this->refreshDevices();

        $this->dispatch('device-forgotten', deviceId: $uuid);
    }

    public function render(): mixed
    {
        return view('livewire.active-devices-panel');
    }
}
