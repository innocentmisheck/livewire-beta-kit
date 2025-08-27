<div wire:poll.60s="fetchTradingData" wire.init="fetchTradingData" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-glass backdrop-blur-md shadow-md dark:bg-neutral-900 p-4">
    <h3 class="text-xl font-bold tracking-tight">{{ $isWalletBased ? 'Wallet' : 'Cryptos' }}</h3>
    <div class="relative w-full h-64">
        <!-- Loading Spinner -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center">
            <div class="flex flex-col items-center justify-center h-24">
                <div class="w-4 h-4 border-3 border-t-transparent border-gray-500 dark:border-gray-400 rounded-full animate-spin"></div>
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
                const chartColor = {
                    border: '#10B981',
                    background: 'rgba(16, 185, 129, 0.2)'
                };
    
                chart = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: chartData.datasets.map((dataset) => ({
                            label: dataset.label || 'Price',
                            data: dataset.prices || [],
                            borderColor: chartColor.border,
                            backgroundColor: createGradient(chartCtx, chartColor.background),
                            fill: true,
                            tension: 0.4,
                            pointRadius: 0,
                            borderWidth: 2,
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        // animations: {
                        // tension: {
                        //     duration: 1000,
                        //     easing: 'easeInOutElastic',
                        //     from: 1,
                        //     to: 0,
                        //     loop: false
                        // }},
                        scales: {
                            x: {
                                display: false,
                                position: 'right',
                                ticks: { color: '#AAA' },
                                label: { display: false, color: '#FFF' },
                                grid: { display: true, color: 'rgba(170, 170, 170, 0.1)',  }
                            },
                            y: {
                                display: true,
                                position: 'right',
                                ticks: {
                                    color: '#AAA',
                                    display: true,
                                    callback: function(value) {
                                        if (value >= 1e9) {
                                            return '$' + (value / 1e9).toFixed(1).replace(/\.0$/, '') + 'B';
                                        } else if (value >= 1e6) {
                                            return '$' + (value / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
                                        } else if (value >= 1e3) {
                                            return '$' + (value / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
                                        } else {
                                            return '$' + value.toLocaleString();
                                        }
                                    }
                                },
                                grid: { display: true, 
                                    color: 'rgba(170, 170, 170, 0.1)',
                                 }
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
                                    align: 'center',
                                    padding: 10,
                                    font: { size: 12 },
                                    color: '#AAA'
                                }
                            },
                          tooltip: {
                            enabled: true,
                            mode: 'nearest',
                            backgroundColor: 'rgba(17, 17, 17, 0.9)', // CoinMarketCap-like dark background
                            titleColor: '#FFFFFF',
                            bodyColor: '#E2E8F0', // Light grey text
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            padding: 10,
                            cornerRadius: 6,
                            borderColor: '#1F2937', // Dark border
                            borderWidth: 1,
                            boxShadow: '0px 4px 10px rgba(0, 0, 0, 0.25)', // Depth effect
                            displayColors: false, // Remove color indicators
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) label += ': ';
                                    const value = context.parsed.y;
                                    if (value >= 1e9) {
                                        return `${label}$${(value / 1e9).toFixed(2)}B`;
                                    } else if (value >= 1e6) {
                                        return `${label}$${(value / 1e6).toFixed(2)}M`;
                                    } else if (value >= 1e3) {
                                        return `${label}$${(value / 1e3).toFixed(2)}K`;
                                    } else {
                                        return `${label}$${value.toLocaleString()}`;
                                    }
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
                gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
                return gradient;
            }
    
            const initialData = @json($chartData);
            console.log('Initial Data:', initialData);
            if (initialData && initialData.labels && initialData.labels.length > 0) {
                updateChart(initialData);
            } else {
                console.warn('No initial data available');
            }
    
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