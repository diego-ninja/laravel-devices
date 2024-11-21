<?php

namespace Ninja\DeviceTracker\Livewire\Components;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ninja\DeviceTracker\Models\Session;

class ActiveSessionsPanel extends Component
{
    public function getSessionsProperty()
    {
        return Auth::user()->sessions()
            ->with(['device'])
            ->active()
            ->get();
    }

    public function endSession($sessionId)
    {
        $session = Session::byUuidOrFail($sessionId);
        $session->end(true, Auth::user());
    }

    public function blockSession($sessionId)
    {
        $session = Session::byUuidOrFail($sessionId);
        $session->block(Auth::user());
    }

    public function unblockSession($sessionId)
    {
        $session = Session::byUuidOrFail($sessionId);
        if (! $session->device->hijacked()) {
            $session->unblock(Auth::user());
        }
    }

    public function render()
    {
        return view('livewire.active-sessions-panel');
    }
}
