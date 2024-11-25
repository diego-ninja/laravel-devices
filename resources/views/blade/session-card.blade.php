<x-card wire:key="{{ $session->uuid }}" class="bg-white">
    <div class="space-y-3">
        {{-- Header con información del dispositivo --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                @if($session->device->device_type === 'mobile')
                    <x-icon name="device-mobile" class="w-5 h-5 text-gray-500" />
                @else
                    <x-icon name="desktop-computer" class="w-5 h-5 text-gray-500" />
                @endif
                <span class="font-medium">{{ $session->device->browser }}</span>
                <span class="text-gray-500">on</span>
                <span class="font-medium">{{ $session->device->platform }}</span>
            </div>

            {{-- Botones de acción --}}
            @unless($isCurrentSession())
                <div class="flex items-center space-x-2">
                    <x-button.circle
                            wire:click="endSession('{{ $session->uuid }}')"
                            flat
                            icon="power"
                            label="End Session"
                    />

                    @if($session->status !== 'blocked')
                        <x-button.circle
                                wire:click="blockSession('{{ $session->uuid }}')"
                                flat
                                negative
                                icon="shield-exclamation"
                                label="Block Session"
                        />
                    @else
                        <x-button.circle
                                wire:click="unblockSession('{{ $session->uuid }}')"
                                flat
                                positive
                                icon="shield-check"
                                label="Unblock Session"
                        />
                    @endif
                </div>
            @endunless
        </div>

        {{-- Información de ubicación y tiempo --}}
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-500">
            <div class="flex items-center space-x-1">
                <x-icon name="globe-alt" class="w-4 h-4" />
                <span class="truncate">
                    {{ $session->location->postal }}, {{ $session->location->city }}, {{ $session->location->country }}
                </span>
            </div>

            <div class="flex items-center space-x-1">
                <x-icon name="clock" class="w-4 h-4" />
                <span>{{ $session->last_activity_at }}</span>
            </div>
        </div>

        {{-- Badge de estado --}}
        <div>
            <x-badge
                    :label="ucfirst($session->status->value)"
                    :icon="$statusIcon"
                    :flat="true"
                    :positive="$session->status === 'active'"
                    :warning="$session->status === 'locked'"
                    :negative="$session->status === 'blocked'"
            />
        </div>
    </div>
</x-card>