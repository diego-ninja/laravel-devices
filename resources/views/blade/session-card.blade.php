<div class="rounded-lg shadow-sm border-l-4 p-4 hover:shadow-md transition-shadow duration-200 {{ $statusClasses() }}">
    <div class="flex items-start justify-between">
        <div class="flex items-start space-x-3">
            <div class="mt-1">
                <x-dynamic-component :component="$deviceIcon()" class="w-8 h-8 text-gray-700" />
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h3 class="font-medium text-gray-900">{{ $session->device->label() }}</h3>
                    @if($isCurrentSession())
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                            {{ __('Current Session') }}
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ $session->device->browser }} on {{ $session->device->platform }}
                </p>
            </div>
        </div>

        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses() }}">
            <x-dynamic-component :component="$statusIcon()" class="w-3 h-3 mr-1" />
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
            {{ $session->last_activity_at }}
        </div>
    </div>

    @if($showActions && !$isCurrentSession())
        <div class="mt-4 flex justify-end space-x-2">
            <x-button
                    wire:click="endSession('{{ $session->uuid }}')"
                    size="sm"
                    class="text-gray-700"
            >
                <x-heroicon-o-power class="w-4 h-4 mr-1" />
                {{ __('End Session') }}
            </x-button>

            @if($session->status !== 'blocked')
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
    @endif
</div>