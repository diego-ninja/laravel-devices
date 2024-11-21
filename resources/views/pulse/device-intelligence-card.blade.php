<x-pulse::card :cols="$cols" :rows="$rows" :class="$class">
    <div class="flex flex-col relative z-10">
        <x-pulse::card-header name="Device Intelligence">
            <x-slot:icon>
                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                </svg>
            </x-slot:icon>
        </x-pulse::card-header>

        <div class="grid grid-cols-4 gap-4 p-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Devices</div>
                <div class="text-2xl font-bold">{{ $stats['total_devices'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Active Today</div>
                <div class="text-2xl font-bold">{{ $stats['active_today'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Outdated Browsers</div>
                <div class="text-2xl font-bold text-orange-500">{{ $stats['outdated_browsers'] }}</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Average Risk</div>
                <div class="text-2xl font-bold">{{ $stats['avg_risk'] }}%</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 p-4">
            <div wire:ignore class="h-64" x-data="deviceCharts(@js($distribution))">
                <canvas x-ref="browsers" class="w-full h-full"></canvas>
            </div>
            <div wire:ignore class="h-64" x-data="deviceCharts(@js($distribution))">
                <canvas x-ref="platforms" class="w-full h-full"></canvas>
            </div>
        </div>

        <div class="p-4">
            <div wire:ignore class="h-32" x-data="deviceTypeChart(@js($distribution))">
                <canvas x-ref="types" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    @script
    <script>
        Alpine.data('deviceCharts', (distribution) => ({
            init() {
                const browserChart = new Chart(this.$refs.browsers, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(distribution.browsers),
                        datasets: [{
                            data: Object.values(distribution.browsers).map(b => b.count),
                            backgroundColor: [
                                '#8b5cf6', '#6366f1', '#ec4899', '#f43f5e', '#f97316'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Browser Distribution'
                            },
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });

                const platformChart = new Chart(this.$refs.platforms, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(distribution.platforms),
                        datasets: [{
                            data: Object.values(distribution.platforms).map(p => p.count),
                            backgroundColor: [
                                '#8b5cf6', '#6366f1', '#ec4899', '#f43f5e', '#f97316'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Platform Distribution'
                            },
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });

                Livewire.on('refresh', ({distribution}) => {
                    browserChart.data.datasets[0].data = Object.values(distribution.browsers).map(b => b.count);
                    browserChart.update();

                    platformChart.data.datasets[0].data = Object.values(distribution.platforms).map(p => p.count);
                    platformChart.update();
                });
            }
        }));

        Alpine.data('deviceTypeChart', (distribution) => ({
            init() {
                const chart = new Chart(this.$refs.types, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(distribution.types),
                        datasets: [{
                            label: 'Devices',
                            data: Object.values(distribution.types).map(t => t.count),
                            backgroundColor: '#8b5cf6'
                        }, {
                            label: 'Risk Score',
                            data: Object.values(distribution.types).map(t => t.risk),
                            backgroundColor: '#f43f5e',
                            yAxisID: 'risk'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            risk: {
                                position: 'right',
                                max: 100,
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        }
                    }
                });

                Livewire.on('refresh', ({distribution}) => {
                    chart.data.datasets[0].data = Object.values(distribution.types).map(t => t.count);
                    chart.data.datasets[1].data = Object.values(distribution.types).map(t => t.risk);
                    chart.update();
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