<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <div class="flex flex-col relative z-10">
        <x-pulse::card-header name="Concurrent Sessions">
            <x-slot:icon>
                <x-dynamic-component component="pulse::icons.session" />
            </x-slot:icon>
            <x-slot:actions>
                <div class="flex flex-grow">
                    <div class="w-full flex items-center gap-4">
                        <div class="flex flex-wrap gap-4">
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                <div class="h-0.5 w-3 rounded-full bg-[#4f46e5]"></div>
                                Active
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                <div class="h-0.5 w-3 rounded-full bg-[#f59e0b]"></div>
                                Locked
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot:actions>
        </x-pulse::card-header>

        <x-pulse::scroll :expand="$expand" wire:poll.5s="">
            <div class="grid grid-cols-1 @lg:grid-cols-3 gap-4 items-center">
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Total Active</div>
                        <div class="text-2xl font-bold">{{ $stats['active'] }}</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Locked Sessions</div>
                        <div class="text-2xl font-bold">{{ $stats['locked'] }}</div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Blocked Sessions</div>
                        <div class="text-2xl font-bold text-red-500">{{ $stats['blocked'] }}</div>
                    </div>
                </div>

                <div class="@lg:col-span-2" wire:ignore>
                    <div class="h-72" x-data="sessionChart(@js($readings))">
                        <canvas x-ref="canvas" class="ring-1 ring-gray-900/5 dark:ring-gray-100/10 bg-gray-50 dark:bg-gray-800 rounded-xl shadow-sm"></canvas>
                    </div>
                </div>
            </div>
        </x-pulse::scroll>
    </div>

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

    @script
    <script>
        Alpine.data('sessionChart', (readings) => ({
            init() {
                let chart = new Chart(this.$refs.canvas, {
                    type: 'line',
                    data: {
                        labels: Object.keys(readings.sessions.active),
                        datasets: [{
                            label: 'Active Sessions',
                            borderColor: '#4f46e5',
                            data: Object.values(readings.sessions.active),
                            order: 1,
                        }, {
                            label: 'Locked Sessions',
                            borderColor: '#f59e0b',
                            data: Object.values(readings.sessions.locked),
                            order: 2,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false,
                            }
                        }
                    }
                });

                Livewire.on('readings-updated', ({readings}) => {
                    chart.data.labels = Object.keys(readings.sessions.active);
                    chart.data.datasets[0].data = Object.values(readings.sessions.active);
                    chart.data.datasets[1].data = Object.values(readings.sessions.locked);
                    chart.update();
                });
            }
        }));
    </script>
    @endscript
</x-pulse::card>