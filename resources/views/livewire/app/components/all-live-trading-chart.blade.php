<div wire:poll.60s="fetchTradingData" wire.mount="fetchTradingData"  class="rounded-xl  border border-neutral-200 dark:border-neutral-700 bg-glass backdrop-blur-md shadow-md dark:bg-neutral-900 p-4">
    <h3 class="text-xl font-bold tracking-tight">Cryptos</h3>
    <div class="relative w-full h-64">
        <!-- Loading Spinner -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center">
            <div class="flex flex-col items-center justify-center h-24">
                <div class="w-4 h-4 border-3 border-t-transparent border-gray-500 dark:border-gray-400 rounded-full animate-spin"></div>
            </div>
        </div>

        <!-- Chart Container -->
        <div wire:loading.remove class="w-full h-64">
            <canvas id="allTradingChart" class="w-full h-64"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', function () {
            const ctx = document.getElementById('allTradingChart');
            if (!ctx) {
                console.error('Canvas element #allTradingChart not found');
                return;
            }
            const chartCtx = ctx.getContext('2d');
            let chart;
    
            function updateChart(chartData) {
                if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                    console.warn('Invalid chart data:', chartData);
                    return;
                }

                // Convert all dataset prices to numbers
                chartData.datasets.forEach(dataset => {
                    dataset.prices = dataset.prices.map(value => Number(value));
                });

                console.log("Chart Data:", chartData); // Debugging output
    
                if (chart) {
                    chart.destroy();
                }
    
                chart = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: chartData.datasets.map((dataset) => ({
                            label: dataset.label,
                            data: dataset.prices,
                            borderColor: dataset.borderColor,
                            backgroundColor: dataset.backgroundColor,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            borderWidth: 2,
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                display: false,
                                ticks: { color: '#AAA' },
                                grid: { display: true, color: 'rgba(170, 170, 170, 0.1)' }
                            },
                            y: {
                                position: 'right',
                                display: true,
                                ticks: {
                                    color: '#AAA',
                                    display: true,
                                    callback: function(value) {
                                        if (typeof value !== 'number') return "N/A";
                                        if (value >= 1e9) return `$${(value / 1e9).toFixed(2)}B`;
                                        if (value >= 1e6) return `$${(value / 1e6).toFixed(2)}M`;
                                        if (value >= 1e3) return `$${(value / 1e3).toFixed(2)}K`;
                                        return `$${value.toLocaleString()}`;
                                    }
                                },
                                grid: { display: true, color: 'rgba(170, 170, 170, 0.1)' }
                            }
                        },
                        plugins: {
                            zoom: {
                                pan: { enabled: true, mode: 'x' },
                                zoom: {
                                    wheel: { enabled: true },
                                    pinch: { enabled: true },
                                    mode: 'x'
                                }
                            },
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 10,
                                    align: 'end',
                                    padding: 10,
                                    font: { size: 12 }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'nearest',
                                backgroundColor: 'rgba(17, 17, 17, 0.9)',
                                titleColor: '#FFFFFF',
                                bodyColor: '#E2E8F0',
                                titleFont: { size: 14, weight: 'bold' },
                                bodyFont: { size: 13 },
                                padding: 10,
                                cornerRadius: 6,
                                borderColor: '#1F2937',
                                borderWidth: 1,
                                boxShadow: '0px 4px 10px rgba(0, 0, 0, 0.25)',
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        let value = context.parsed.y;
                                        if (typeof value !== 'number') return label + "N/A";
                                        if (value >= 1e9) return `${label}$${(value / 1e9).toFixed(2)}B`;
                                        if (value >= 1e6) return `${label}$${(value / 1e6).toFixed(2)}M`;
                                        if (value >= 1e3) return `${label}$${(value / 1e3).toFixed(2)}K`;
                                        return `${label}$${value.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        hover: { mode: 'nearest', intersect: false },
                    }
                });
            }
    
            // Initial chart load
            const initialData = @json($chartData);
            if (initialData && initialData.labels && initialData.labels.length > 0) {
                updateChart(initialData);
            }
    
            // Listen for Livewire updates
            Livewire.on('data-updated', (event) => {
                updateChart(event);
            });
        });
    </script>
</div>
