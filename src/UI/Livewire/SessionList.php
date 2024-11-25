<?php

namespace Ninja\DeviceTracker\UI\Livewire;

use Livewire\Component;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\Exception\SessionNotFoundException;
use Ninja\DeviceTracker\Factories\SessionIdFactory;
use Ninja\DeviceTracker\Models\Session;

class SessionList extends Component
{
    public bool $confirmingEnd = false;

    public bool $confirmingBlock = false;

    public ?StorableId $selectedSession = null;

    protected $listeners = ['session-status-changed' => '$refresh'];

    public function confirmEndSession(string $sessionId): void
    {
        $this->selectedSession = SessionIdFactory::from($sessionId);
        $this->confirmingEnd = true;
    }

    public function confirmBlockSession(string $sessionId): void
    {
        $this->selectedSession = SessionIdFactory::from($sessionId);
        $this->confirmingBlock = true;
    }

    /**
     * @throws SessionNotFoundException
     */
    public function endSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->end(forgetSession: true);
            $this->dispatch('session-status-changed')->self();
            $this->dispatch('notify', [
                'message' => __('Session ended successfully'),
                'type' => 'success',
            ]);
        }

        $this->confirmingEnd = false;
    }

    /**
     * @throws SessionNotFoundException
     */
    public function blockSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->block();
            $this->dispatch('session-status-changed')->self();
            $this->dispatch('notify', [
                'message' => __('Session blocked successfully'),
                'type' => 'success',
            ]);
        }

        $this->confirmingBlock = false;
    }

    /**
     * @throws SessionNotFoundException
     */
    public function unblockSession(string $sessionId): void
    {
        $session = Session::byUuid($sessionId);

        if ($session) {
            $session->unblock();
            $this->dispatch('session-status-changed')->self();
            $this->dispatch('notify', [
                'message' => __('Session unblocked successfully'),
                'type' => 'success',
            ]);
        }
    }

    public function render(): mixed
    {
        $sessions = auth()->user()
            ->sessions
            ->load('device')
            ->sortByDesc('started_at');


        return view('devices::livewire.session-list', [
            'sessions' => $sessions,
        ]);
    }
}
