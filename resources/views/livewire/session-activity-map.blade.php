<div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-medium text-gray-900">Geographic Activity</h2>

            <div class="inline-flex rounded-md shadow-sm">
                <button wire:click="setTimeRange('24h')" class="@if($timeRange === '24h') bg-indigo-600 text-white @else bg-white text-gray-700 @endif px-4 py-2 text-sm font-medium border rounded-l-md hover:bg-gray-50">
                    24h
                </button>
                <button wire:click="setTimeRange('7d')" class="@if($timeRange === '7d') bg-indigo-600 text-white @else bg-white text-gray-700 @endif px-4 py-2 text-sm font-medium border-t border-b border-r hover:bg-gray-50">
                    7d
                </button>
                <button wire:click="setTimeRange('30d')" class="@if($timeRange === '30d') bg-indigo-600 text-white @else bg-white text-gray-700 @endif px-4 py-2 text-sm font-medium border rounded-r-md hover:bg-gray-50">
                    30d
                </button>
            </div>
        </div>

        <div id="activity-map" class="h-96 rounded-lg"></div>

        <div class="mt-4 space-y-4">
            @foreach($sessions as $session)
                <div class="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                        <h3 class="font-medium text-gray-900">{{ $session->device->device_family }}</h3>
                        <p class="text-sm text-gray-500">{{ $session->location->city }}, {{ $session->location->country }}</p>
                        <p class="text-xs text-gray-400">Last active: {{ $session->last_activity_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $session->events->count() }} events
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:initialized', () => {
                const map = L.map('activity-map');
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                const bounds = L.latLngBounds();
                const sessions = @json($sessions);

                sessions.forEach(session => {
                    const loc = session.location;
                    const marker = L.marker([loc.latitude, loc.longitude])
                        .bindPopup(`
                        <b>${session.device.device_family}</b><br>
                        ${loc.city}, ${loc.country}<br>
                        ${session.events.length} events
                    `)
                        .addTo(map);

                    bounds.extend([loc.latitude, loc.longitude]);
                });

                if (!bounds.isEmpty()) {
                    map.fitBounds(bounds);
                }
            });
        </script>
    @endpush
</div>