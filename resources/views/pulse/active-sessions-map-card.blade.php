<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <div class="flex flex-col relative z-10">
        <x-pulse::card-header name="Active Sessions Map">
            <x-slot:icon>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                </svg>
            </x-slot:icon>
        </x-pulse::card-header>

        <div class="grid grid-cols-4 gap-4 p-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Sessions</div>
                <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active Sessions</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Inactive Sessions</div>
                <div class="text-2xl font-bold text-yellow-500">{{ $stats['inactive'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Blocked/Locked</div>
                <div class="text-2xl font-bold text-red-500">{{ $stats['blocked'] }}</div>
            </div>
        </div>

        <div wire:ignore class="h-[32rem] px-4">
            <div x-data="sessionMap(@js($sessions))" class="h-full">
                <div x-ref="map" class="h-full rounded-xl"></div>

                <template x-teleport="body">
                    <div x-show="selected" x-transition class="fixed inset-0 z-50 overflow-y-auto" @click.self="selected = null">
                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                                <div x-show="selected">
                                    <div class="border-b border-gray-200 dark:border-gray-700">
                                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                            <button
                                                    @click="tab = 'user'"
                                                    :class="tab === 'user' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                    class="border-b-2 py-4 px-1 text-sm font-medium">
                                                User
                                            </button>
                                            <button
                                                    @click="tab = 'device'"
                                                    :class="tab === 'device' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                    class="border-b-2 py-4 px-1 text-sm font-medium">
                                                Device
                                            </button>
                                            <button
                                                    @click="tab = 'session'"
                                                    :class="tab === 'session' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400'"
                                                    class="border-b-2 py-4 px-1 text-sm font-medium">
                                                Session
                                            </button>
                                        </nav>
                                    </div>

                                    <div class="mt-4">
                                        <div x-show="tab === 'user'" class="space-y-4">
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Name</div>
                                                <div class="font-medium" x-text="selected.user.name"></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Email</div>
                                                <div class="font-medium" x-text="selected.user.email"></div>
                                            </div>
                                            <button
                                                    @click="logout(selected.user.id)"
                                                    class="w-full bg-red-500 text-white px-4 py-2 rounded-lg">
                                                Logout All Sessions
                                            </button>
                                        </div>

                                        <div x-show="tab === 'device'" class="space-y-4">
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Device</div>
                                                <div class="font-medium" x-text="selected.device.name"></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Browser</div>
                                                <div class="font-medium" x-text="selected.device.browser"></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Platform</div>
                                                <div class="font-medium" x-text="selected.device.platform"></div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button
                                                        @click="hijackDevice(selected.device.uuid)"
                                                        class="flex-1 bg-yellow-500 text-white px-4 py-2 rounded-lg"
                                                        :disabled="selected.device.hijacked">
                                                    Mark as Hijacked
                                                </button>
                                                <button
                                                        @click="forgetDevice(selected.device.uuid)"
                                                        class="flex-1 bg-red-500 text-white px-4 py-2 rounded-lg">
                                                    Forget Device
                                                </button>
                                            </div>
                                        </div>

                                        <div x-show="tab === 'session'" class="space-y-4">
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">IP Address</div>
                                                <div class="font-medium" x-text="selected.session.ip"></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Location</div>
                                                <div class="font-medium" x-text="selected.session.location"></div>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">Last Activity</div>
                                                <div class="font-medium" x-text="selected.session.last_activity"></div>
                                            </div>
                                            <div class="flex gap-2">
                                                <button
                                                        @click="endSession(selected.id)"
                                                        class="flex-1 bg-red-500 text-white px-4 py-2 rounded-lg">
                                                    End Session
                                                </button>
                                                <template x-if="!selected.device.hijacked">
                                                    <button
                                                            @click="toggleSessionBlock(selected.id)"
                                                            :class="selected.status === 'blocked' ? 'bg-green-500' : 'bg-yellow-500'"
                                                            class="flex-1 text-white px-4 py-2 rounded-lg">
                                                        <span x-text="selected.status === 'blocked' ? 'Unblock' : 'Block'"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    @endpush

    @script
    <script>
        Alpine.data('sessionMap', (sessions) => ({
            map: null,
            markers: {},
            selected: null,
            tab: 'user',

            init() {
                this.map = L.map(this.$refs.map).setView([0, 0], 2);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

                this.updateMarkers(sessions);

                Livewire.on('refresh', ({sessions}) => {
                    this.updateMarkers(sessions);
                });
            },

            updateMarkers(sessions) {
                // Remove old markers
                Object.values(this.markers).forEach(marker => marker.remove());
                this.markers = {};

                // Add new markers
                sessions.forEach(session => {
                    const color = session.status === 'blocked' || session.status === 'locked'
                        ? 'red'
                        : (session.inactive ? 'yellow' : 'green');

                    const marker = L.circleMarker([session.lat, session.lng], {
                        radius: 8,
                        fillColor: color,
                        color: '#fff',
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8
                    }).addTo(this.map);

                    marker.on('click', () => {
                        this.selected = session;
                        this.tab = 'user';
                    });

                    this.markers[session.id] = marker;
                });

                if (Object.keys(this.markers).length > 0) {
                    this.map.fitBounds(Object.values(this.markers).map(m => m.getLatLng()));
                }
            },

            async logout(userId) {
                await fetch(`/api/sessions/signout`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ user_id: userId })
                });
                this.selected = null;
                Livewire.emit('refresh');
            },

            async hijackDevice(deviceId) {
                await fetch(`/api/devices/${deviceId}/hijack`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.selected = null;
                Livewire.emit('refresh');
            },

            async forgetDevice(deviceId) {
                await fetch(`/api/devices/${deviceId}/forget`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.selected = null;
                Livewire.emit('refresh');
            },

            async endSession(sessionId) {
                await fetch(`/api/sessions/${sessionId}/end`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.selected = null;
                Livewire.emit('refresh');
            },

            async toggleSessionBlock(sessionId) {
                const endpoint = this.selected.status === 'blocked'
                    ? `/api/sessions/${sessionId}/unblock`
                    : `/api/sessions/${sessionId}/block`;

                await fetch(endpoint, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.selected = null;
                Livewire.emit('refresh');
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