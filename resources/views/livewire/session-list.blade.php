<div>
    <div class="space-y-6">
        @forelse($sessions as $session)
            <x-session-card :session="$session" />
        @empty
            <div class="text-center py-12">
                <x-heroicon-o-device-phone-mobile class="mx-auto h-12 w-12 text-gray-400"/>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No active sessions') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('There are no active sessions for your account.') }}</p>
            </div>
        @endforelse
    </div>

    <x-dialog-modal wire:model="confirmingEnd">
        <x-slot name="title">
            {{ __('End Session') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to end this session? The user will be logged out immediately.') }}
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="$set('confirmingEnd', false)">
                {{ __('Cancel') }}
            </x-button>

            <x-button
                    wire:click="endSession('{{ $selectedSession }}')"
                    class="ml-3"
                    variant="danger"
            >
                {{ __('End Session') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-dialog-modal wire:model="confirmingBlock">
        <x-slot name="title">
            {{ __('Block Session') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to block this session? The user will be logged out and unable to log in using this device.') }}
        </x-slot>

        <x-slot name="footer">
            <x-button wire:click="$set('confirmingBlock', false)">
                {{ __('Cancel') }}
            </x-button>

            <x-button
                    wire:click="blockSession('{{ $selectedSession }}')"
                    class="ml-3"
                    variant="danger"
            >
                {{ __('Block Session') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>