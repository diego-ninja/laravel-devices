<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <div class="flex flex-col relative z-10">
        <x-pulse::card-header name="Geographic Distribution">
            <x-slot:icon>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" />
                </svg>
            </x-slot:icon>
        </x-pulse::card-header>

        <div class="grid grid-cols-4 gap-4 p-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Countries</div>
                <div class="text-2xl font-bold">{{ $total['countries'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Cities</div>
                <div class="text-2xl font-bold">{{ $total['cities'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Users</div>
                <div class="text-2xl font-bold">{{ $total['users'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Sessions</div>
                <div class="text-2xl font-bold">{{ $total['sessions'] }}</div>
            </div>
        </div>

        <div class="flex flex-1">
            <div class="w-1/2 p-4">
                <div wire:ignore class="h-[24rem]" x-data="geoDistribution(@js($locations))">
                    <div x-ref="map" class="h-full rounded-xl"></div>
                </div>
            </div>
            <div class="w-1/2 p-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 h-[24rem]">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Top Countries</h3>
                    <div class="space-y-4">
                        @foreach($topCountries as $country => $stats)
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="font-medium">{{ $country }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $stats['cities'] }} cities, {{ $stats['users'] }} users
                                    </div>
                                </div>
                                <div class="text-sm font-medium">
                                    {{ $stats['sessions'] }} sessions
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    @endpush

    @script
    <script>
        Alpine.data('geoDistribution', (locations) => ({
            init() {
                const map = L.map(this.$refs.map).setView([0, 0], 2);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

                Object.entries(locations).forEach(([country, cities]) => {
                    cities.forEach(city => {
                        if (city.location?.latitude && city.location?.longitude) {
                            const radius = Math.sqrt(city.sessions) * 5;
                            L.circleMarker([city.location.latitude, city.location.longitude], {
                                radius,
                                fillColor: '#0ea5e9',
                                color: '#fff',
                                weight: 1,
                                opacity: 1,
                                fillOpacity: 0.8
                            })
                                .bindPopup(`
                                <b>${city.city}, ${country}</b><br>
                                ${city.sessions} sessions<br>
                                ${city.devices} devices<br>
                                ${city.users} users
                            `)
                                .addTo(map);
                        }
                    });
                });
            }
        }));
    </script>
    @endscript

    <div class="absolute bottom-0 left-0 w-full h-10 z-0 overflow-hidden rounded-xl">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none">
            <defs>
                <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
            </defs>
            <g class="waves">
                <use xlink:href="#gentle-wave" x="50" y="0" fill="{{ $color }}" fill-opacity=".2" />
                <use xlink:href="#gentle-wave" x="50" y="3" fill="{{ $color }}" fill-opacity=".25" />
                <use xlink:href="#gentle-wave" x="50" y="6" fill="{{ $color }}" fill-opacity=".3" />
            </g>
        </svg>
    </div>
</x-pulse::card>