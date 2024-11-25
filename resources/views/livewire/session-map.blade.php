<div
        x-data="sessionMap(@entangle('selectedSession'))"
        x-init="initialize(@js($mapboxToken), @js($mapboxStyle), @js($mapBounds))"
        wire:ignore
        class="relative w-full h-[600px] bg-gray-100 rounded-lg overflow-hidden"
>
    <div id="map" class="absolute inset-0"></div>

    <template x-if="selectedSession">
        <div
                x-show="selectedMarkerPosition"
                :style="`position: absolute; left: ${selectedMarkerPosition?.x}px; top: ${selectedMarkerPosition?.y}px;`"
                class="z-50"
        >
            <x-devices::session-tooltip :session="null" />
        </div>
    </template>
</div>

@push('scripts')
    <script src='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css' rel='stylesheet' />

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sessionMap', (selectedSession) => ({
                map: null,
                markers: {},
                selectedMarkerPosition: null,

                async initialize(token, style, bounds) {
                    mapboxgl.accessToken = token;

                    this.map = new mapboxgl.Map({
                        container: 'map',
                        style: style,
                        bounds: [bounds.west, bounds.south, bounds.east, bounds.north],
                        fitBoundsOptions: { padding: 50 }
                    });

                    this.map.addControl(new mapboxgl.NavigationControl());

                    this.map.on('load', () => this.addMarkers());

                    this.$watch('selectedSession', (value) => {
                        if (value && this.markers[value]) {
                            const marker = this.markers[value];
                            const point = this.map.project(marker.getLngLat());
                            this.selectedMarkerPosition = { x: point.x, y: point.y };
                        } else {
                            this.selectedMarkerPosition = null;
                        }
                    });
                },

                addMarkers() {
                    @foreach($sessions as $session)
                        this.addMarker(@js([
                    'id' => $session->uuid,
                    'lat' => $session->location->latitude,
                    'lng' => $session->location->longitude,
                    'status' => $session->status->value
                ]));
                    @endforeach
                },

                addMarker(session) {
                    const el = document.createElement('div');
                    el.className = 'session-marker';
                    el.innerHTML = this.getMarkerHtml(session.status);

                    const marker = new mapboxgl.Marker(el)
                        .setLngLat([session.lng, session.lat])
                        .addTo(this.map);

                    el.addEventListener('click', () => {
                        this.$wire.selectSession(session.id);
                    });

                    this.markers[session.id] = marker;
                },

                getMarkerHtml(status) {
                    const colors = {
                        active: { outer: 'bg-green-500', inner: 'bg-green-200' },
                        locked: { outer: 'bg-yellow-500', inner: 'bg-yellow-200' },
                        blocked: { outer: 'bg-red-500', inner: 'bg-red-200' }
                    };

                    return `
                <div class="w-6 h-6 rounded-full ${colors[status].outer} shadow-lg flex items-center justify-center cursor-pointer hover:scale-110 transition-transform duration-200">
                    <div class="w-4 h-4 rounded-full ${colors[status].inner} flex items-center justify-center">
                        <div class="w-2 h-2 rounded-full ${colors[status].outer}"></div>
                    </div>
                </div>
            `;
                },

                updateMarkerStatus(sessionId, status) {
                    const marker = this.markers[sessionId];
                    if (marker) {
                        const el = marker.getElement();
                        el.innerHTML = this.getMarkerHtml(status);
                    }
                },

                removeMarker(sessionId) {
                    const marker = this.markers[sessionId];
                    if (marker) {
                        marker.remove();
                        delete this.markers[sessionId];
                    }
                }
            }));
        });

        // Event Listeners for marker updates
        window.addEventListener('session-ended', event => {
            const map = Alpine.raw(document.querySelector('[x-data="sessionMap"]').__x.$data);
            map.removeMarker(event.detail.sessionId);
        });

        window.addEventListener('session-blocked', event => {
            const map = Alpine.raw(document.querySelector('[x-data="sessionMap"]').__x.$data);
            map.updateMarkerStatus(event.detail.sessionId, 'blocked');
        });

        window.addEventListener('session-unblocked', event => {
            const map = Alpine.raw(document.querySelector('[x-data="sessionMap"]').__x.$data);
            map.updateMarkerStatus(event.detail.sessionId, 'active');
        });
    </script>
@endpush