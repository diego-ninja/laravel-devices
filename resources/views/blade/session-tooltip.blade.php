<div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 w-80 bg-white rounded-lg shadow-xl border-l-4 p-4 z-50">
    <div class="flex items-start justify-between">
        <div class="flex items-start space-x-3">
            <div class="mt-1">
                <x-dynamic-component :component="$deviceIcon" class="w-8 h-8 text-gray-700" />
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h3 class="font-medium text-gray-900">{{ $session->device->label() }}</h3>
                    @if($session->isCurrent())
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                            {{ __('Current') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ $session->device->browser }} on {{ $session->device->platform }}
                </p>
            </div>
        </div>

        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
            @if($session->status->value === 'locked')
                <x-heroicon-s-lock-closed class="w-3 h-3 mr-1" />
            @elseif($session->status->value === 'blocked')
                <x-heroicon-s-x-circle class="w-3 h-3 mr-1" />
            @else
                <x-heroicon-s-shield-check class="w-3 h-3 mr-1" />
            @endif
            {{ ucfirst($session->status->value) }}
        </span>
    </div>

    <div class="mt-4 grid grid-cols-2 gap-4">
        <div class="flex items-center text-sm text-gray-500">
            <x-heroicon-o-globe-alt class="w-4 h-4 mr-2 flex-shrink-0" />
            <span class="truncate">
                {{ $session->location->postal }}, {{ $session->location->city }}, {{ $session->location->country }}
            </span>
        </div>
        <div class="flex items-center text-sm text-gray-500">
            <x-heroicon-o-clock class="w-4 h-4 mr-2 flex-shrink-0" />
            {{ $session->last_activity_at->diffForHumans() }}
        </div>
    </div>

    @unless($session->isCurrent())
        <div class="mt-4 flex justify-end space-x-2">
            <x-button
                    wire:click="endSession('{{ $session->uuid }}')"
                    size="sm"
                    class="text-gray-700"
            >
                <x-heroicon-o-power class="w-4 h-4 mr-1" />
                {{ __('End Session') }}
            </x-button>

            @if($session->status->value !== 'blocked')
                <x-button
                        wire:click="blockSession('{{ $session->uuid }}')"
                        size="sm"
                        class="text-red-700 border-red-300 hover:bg-red-50"
                >
                    <x-heroicon-o-x-circle class="w-4 h-4 mr-1" />
                    {{ __('Block Session') }}
                </x-button>
            @else
                <x-button
                        wire:click="unblockSession('{{ $session->uuid }}')"
                        size="sm"
                        class="text-green-700 border-green-300 hover:bg-green-50"
                >
                    <x-heroicon-o-shield-check class="w-4 h-4 mr-1" />
                    {{ __('Unblock Session') }}
                </x-button>
            @endif
        </div>
    @endunless

    <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-4 h-4 rotate-45 bg-white border-r border-b"></div>
</div>