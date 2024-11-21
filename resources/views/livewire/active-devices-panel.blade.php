<div>
    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Active Devices
                    </div>
                </h2>
            </div>

            <div class="space-y-6">
                @foreach($devices as $device)
                    <div class="border rounded-lg p-4" wire:key="device-{{ $device->uuid }}">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold">
                                    {{ $device->device_family }} {{ $device->device_model }}
                                </h3>
                                <p class="text-sm text-gray-500">
                                    {{ $device->browser }} {{ $device->browser_version }} on {{ $device->platform }} {{ $device->platform_version }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                        wire:click="hijackDevice('{{ $device->uuid }}')"
                                        wire:confirm="Are you sure you want to mark this device as hijacked?"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                                        title="Mark as Hijacked"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/>
                                    </svg>
                                </button>
                                <button
                                        wire:click="signoutDevice('{{ $device->uuid }}')"
                                        wire:confirm="Are you sure you want to sign out all sessions for this device?"
                                        class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg"
                                        title="Sign Out Sessions"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                </button>
                                <button
                                        wire:click="forgetDevice('{{ $device->uuid }}')"
                                        wire:confirm="Are you sure you want to forget this device? This will end all active sessions."
                                        class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg"
                                        title="Forget Device"
                                >
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @if($device->sessions->isNotEmpty())
                            @php
                                $activeSession = $device->sessions->first();
                                $location = $activeSession->location;
                            @endphp
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span>{{ $location->city }}, {{ $location->country }}</span>
                            </div>

                            <div id="map-{{ $device->uuid }}" class="mt-4 rounded-lg h-48"></div>

                            @push('scripts')
                                <script>
                                    document.addEventListener('livewire:initialized', () => {
                                        const map = L.map('map-{{ $device->uuid }}').setView(
                                            [{{ $location->latitude }}, {{ $location->longitude }}],
                                            13
                                        );

                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                                        L.marker([{{ $location->latitude }}, {{ $location->longitude }}])
                                            .addTo(map)
                                            .bindPopup("{{ $device->device_family }} {{ $device->device_model }}");
                                    });
                                </script>
                            @endpush
                        @else
                            <div class="rounded-md bg-yellow-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            No active session for this device
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
</div>