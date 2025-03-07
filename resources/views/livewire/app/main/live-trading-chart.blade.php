<div wire:poll.5s="fetchTradingData" wire:mount="fetchTradingData"  class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Trading Chart</h3>
    <div class="relative w-full h-64">
        <!-- Loading Spinner -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center">
            <div class="flex flex-col items-center justify-center h-24">
{{--                <p class="text-center text-gray-500 font-bold">Loading Data</p>--}}
                <div class="w-4 h-4 border-3 border-t-transparent border-gray-500 dark:border-gray-400 rounded-full animate-spin"></div>
{{--                <p class="text-center text-gray-500 font-bold mt-1 text-xs">Please wait...</p>--}}
            </div>
        </div>

        <!-- Chart Container -->
        <div wire:loading.remove class="w-full h-64">
            <canvas id="tradingChart" class="w-full h-64"></canvas>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', function () {
            const ctx = document.getElementById('tradingChart');
            if (!ctx) {
                console.error('Canvas element #tradingChart not found');
                return;
            }
            const chartCtx = ctx.getContext('2d');
            let chart;

            function updateChart(chartData) {
                if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                    console.warn('Invalid chart data:', chartData);
                    return;
                }

                if (chart) {
                    chart.destroy();
                }

                const colors = [
                    { border: '#FFCE56', background: 'rgba(255, 206, 86, 0.2)' }, // BTC
                    { border: '#36A2EB', background: 'rgba(54, 162, 235, 0.2)' }, // ETH
                    { border: '#FF6384', background: 'rgba(255, 99, 132, 0.2)' }, // LTC
                    { border: '#4BC0C0', background: 'rgba(75, 192, 192, 0.2)' }, // XRP
                    { border: '#9966FF', background: 'rgba(153, 102, 255, 0.2)' } // ADA
                ];

                chart = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: chartData.datasets.map((dataset, index) => ({
                            label: index < 5 ? dataset.label || 'Unknown' : '', // Only show top 5 in legend
                            data: dataset.prices || [],
                            borderColor: colors[index % colors.length].border,
                            backgroundColor: createGradient(chartCtx, colors[index % colors.length].background),
                            fill: true,
                            tension: 0.4, // Smooth curve effect
                            pointRadius: 0, // Hide points for a clean look
                            borderWidth: 2,
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                ticks: {
                                    color: '#AAA',


                                },
                                grid: { display: false }
                            },
                            y: {
                                ticks: {
                                    color: '#FFF',
                                    display: false,
                                    callback: function(value) {
                                        return '$' + value.toLocaleString(); // Format prices
                                    }
                                },
                                grid: { color: 'rgba(255, 255, 255)', display: false }
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
                                position: 'bottom',

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
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#FFF',
                                bodyColor: '#FFF',
                                borderColor: '#FFF',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        return `${label}$${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        hover: { mode: 'nearest', intersect: false },
                    }
                });
            }

            function createGradient(ctx, color) {
                let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, color);
                gradient.addColorStop(1, 'rgba(0,0,0,0)');
                return gradient;
            }

            // Initial chart load
            const initialData = @json($chartData);
            console.log('Initial Data:', initialData);
            if (initialData && initialData.labels && initialData.labels.length > 0) {
                updateChart(initialData);
            } else {
                console.warn('No initial data available');
            }

            // Listen for Livewire updates
            Livewire.on('data-updated', (event) => {
                const updatedData = event;
                console.log('Data Updated Event:', updatedData);
                if (updatedData && updatedData.labels && updatedData.labels.length > 0) {
                    updateChart(updatedData);
                } else {
                    console.warn('Updated data invalid or empty:', updatedData);
                }
            });
        });
    </script>

</div>
