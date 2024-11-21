<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
            Two Factor Authentication
        </h2>

        <div class="mt-4">
            @if($message)
                <div class="mb-4 rounded-md {{ str_contains($message, 'Invalid') ? 'bg-red-50' : 'bg-green-50' }} p-4">
                    <p class="{{ str_contains($message, 'Invalid') ? 'text-red-700' : 'text-green-700' }}">
                        {{ $message }}
                    </p>
                </div>
            @endif

            @if(!$enabled)
                <button wire:click="enable" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Enable Two-Factor Authentication
                </button>
            @else
                <div class="space-y-6">
                    @if($qrCode)
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scan this QR Code</label>
                            <div class="mt-2 bg-white p-4 inline-block rounded-lg">
                                {!! $qrCode !!}
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" wire:model="code" id="code" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Enter 6-digit code">
                            <button wire:click="verifyCode" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                Verify
                            </button>
                        </div>
                    </div>

                    <button wire:click="disable" wire:confirm="Are you sure you want to disable two-factor authentication?" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200">
                        Disable Two-Factor Authentication
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>