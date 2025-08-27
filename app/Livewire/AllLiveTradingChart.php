<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\LiveCoinWatchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AllLiveTradingChart extends Component
{
    // Configuration constants
    private const MAX_CRYPTOCURRENCIES = 5; // Max cryptos to display
    private const CACHE_TTL_PRICES = 1; // 1 minutes
    private const CACHE_TTL_HISTORICAL_DATA = 60; // 24 hour
    private const HISTORICAL_DATA_POINTS = 60; // Data points for the chart

    public $chartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $cryptoSymbols = [];
    public $errorMessage = null;

    protected $liveCoinWatchService;

    public function boot(LiveCoinWatchService $liveCoinWatchService)
    {
        $this->liveCoinWatchService = $liveCoinWatchService;
    }

    

    public function mount()
    {
        try {
            $this->initializeTradingData();
        } catch (\Exception $e) {
            $this->handleCriticalError($e);
        }
    }

    protected function initializeTradingData()
    {
        $this->cryptoSymbols = $this->fetchTopCryptoSymbols();
        $this->fetchTradingData();
    }

    protected function fetchTopCryptoSymbols(): array
    {
        $cacheKey = 'top_crypto_symbols';
        $cachedSymbols = Cache::get($cacheKey);

        if ($cachedSymbols) {
            return $cachedSymbols;
        }

        try {
            $symbols = $this->liveCoinWatchService->getCurrenciesList();
            $symbols = collect($symbols)
                ->pluck('code')
                ->take(self::MAX_CRYPTOCURRENCIES)
                ->toArray();

            Cache::put($cacheKey, $symbols, now()->addMinutes(self::CACHE_TTL_HISTORICAL_DATA));
            return $symbols;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch top crypto symbols: ' . $e->getMessage());
            return ['BTC', 'ETH', 'BNB', 'XRP', 'ADA', 'SOL']; // Fallback symbols
        }
    }

    public function fetchTradingData()
    {
        try {
            $this->prepareTimeLabels();
            $currentPrices = $this->fetchCurrentPrices();
            $historicalData = $this->manageHistoricalData($currentPrices);
            $this->prepareChartDatasets($historicalData, $currentPrices);
            $this->dispatch('data-updated', $this->chartData);
        } catch (\Exception $e) {
            $this->handleCriticalError($e);
        }
    }

    protected function prepareTimeLabels()
    {
        $now = now();
        $this->chartData['labels'] = collect(range(-self::HISTORICAL_DATA_POINTS + 1, 0))
            ->map(fn($offset) => $now->copy()->addMinutes($offset)->format('H:i'))
            ->toArray();
    }

    protected function fetchCurrentPrices(): array
    {
        $cacheKey = 'all_crypto_current_prices';
        $cachedPrices = Cache::get($cacheKey);

        if ($cachedPrices) {
            return $cachedPrices;
        }

        try {
            $coinDetails = $this->liveCoinWatchService->getCoinDetails($this->cryptoSymbols);
            $prices = [];
            foreach ($this->cryptoSymbols as $symbol) {
                $prices[$symbol] = [
                    'rate' => $coinDetails[$symbol]['price'] ?? 0
                ];
            }
            Cache::put($cacheKey, $prices, now()->addMinutes(self::CACHE_TTL_PRICES));
            return $prices;
        } catch (\Exception $e) {
            Log::warning('Price fetch failed: ' . $e->getMessage());
            return array_fill_keys($this->cryptoSymbols, ['rate' => 0]);
        }
    }

    protected function manageHistoricalData(array $currentPrices): array
    {
        $cacheKey = 'all_trading_historical_data';
        $historicalData = Cache::get($cacheKey, array_fill_keys($this->cryptoSymbols, array_fill(0, self::HISTORICAL_DATA_POINTS, 0)));

        foreach ($this->cryptoSymbols as $symbol) {
            $prices = $historicalData[$symbol] ?? array_fill(0, self::HISTORICAL_DATA_POINTS, 0);
            array_shift($prices);
            $newPrice = $currentPrices[$symbol]['rate'] ?? 0;
            array_push($prices, $newPrice);
            $historicalData[$symbol] = $prices;
        }

        Cache::put($cacheKey, $historicalData, now()->addHour());
        return $historicalData;
    }

    protected function prepareChartDatasets(array $historicalData, array $currentPrices)
    {
        $this->chartData['datasets'] = [];

        $colors = [
            ['border' => '#FFCE56', 'background' => 'rgba(255, 206, 86, 0.2)'], // BTC
            ['border' => '#36A2EB', 'background' => 'rgba(54, 162, 235, 0.2)'], // ETH
            ['border' => '#FF6384', 'background' => 'rgba(255, 99, 132, 0.2)'], // LTC
            ['border' => '#4BC0C0', 'background' => 'rgba(75, 192, 192, 0.2)'], // XRP
            ['border' => '#9966FF', 'background' => 'rgba(153, 102, 255, 0.2)'] // ADA
        ];

        foreach ($this->cryptoSymbols as $index => $symbol) {
            $this->chartData['datasets'][] = [
                'label' => strtoupper($symbol),
                'prices' => $historicalData[$symbol],
                'borderColor' => $colors[$index % count($colors)]['border'],
                'backgroundColor' => $colors[$index % count($colors)]['background']
            ];
        }
    }

    protected function handleCriticalError(\Exception $e)
    {
        Log::error('Critical error in trading chart: ' . $e->getMessage());
        $this->errorMessage = 'Unable to load trading data. Please try again later.';
        $this->chartData = [
            'labels' => [now()->format('H:i')],
            'datasets' => array_map(
                fn($symbol) => [
                    'label' => strtoupper($symbol),
                    'prices' => [0]
                ],
                $this->cryptoSymbols
            )
        ];
    }

    public function render()
    {
        return view('livewire.app.components.all-live-trading-chart', [
            'chartData' => $this->chartData,
            'errorMessage' => $this->errorMessage
        ])
        ->with(['livewire polling' => '10s']);

    }
}