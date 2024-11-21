<div class="bg-white shadow-xl sm:rounded-lg">
    <div class="p-6">
        <h2 class="text-lg font-medium flex items-center gap-2 text-gray-900">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Active Sessions
        </h2>

        <div class="mt-6 space-y-4">
            @foreach($this->sessions as $session)
                <div class="border rounded-lg p-4" wire:key="session-{{ $session->uuid }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $session->device->device_family }} {{ $session->device->device_model }}
                                </h3>
                                @if($session->device->status === DeviceStatus::Hijacked)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Hijacked Device
                                </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                IP: {{ $session->ip }} • {{ $session->location->city }}, {{ $session->location->country }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                Last activity: {{ $session->last_activity_at->diffForHumans() }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                    wire:click="endSession('{{ $session->uuid }}')"
                                    wire:confirm="Are you sure you want to end this session?"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                    title="End Session"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            @if($session->blocked())
                                <button
                                        wire:click="unblockSession('{{ $session->uuid }}')"
                                        @if($session->device->status === DeviceStatus::Hijacked)
                                            disabled
                                        class="p-2 text-gray-400 cursor-not-allowed"
                                        title="Cannot unblock session from hijacked device"
                                        @else
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg"
                                        title="Unblock Session"
                                        @endif
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            @else
                                <button
                                        wire:click="blockSession('{{ $session->uuid }}')"
                                        wire:confirm="Are you sure you want to block this session?"
                                        class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg"
                                        title="Block Session"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            @if($this->sessions->isEmpty())
                <div class="text-center py-4 text-gray-500">
                    No active sessions found
                </div>
            @endif
        </div>
    </div>
</div>