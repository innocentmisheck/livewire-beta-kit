<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LiveTradingChart extends Component
{
    // Configuration constants
    private const MAX_CRYPTOCURRENCIES = 5;
    private const CACHE_TTL_SYMBOLS = 24 * 60; // 24 hours
    private const CACHE_TTL_PRICES = 5; // 5 minutes
    private const HISTORICAL_DATA_POINTS = 60;

    public $chartData = [
        'labels' => [],
        'datasets' => [],
    ];

    public $cryptoSymbols = [];
    public $errorMessage = null;

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
        // Fetch symbols with more robust error handling
        $this->cryptoSymbols = $this->safelyFetchCryptoSymbols();

        // Fetch trading data
        $this->fetchTradingData();
    }

    protected function safelyFetchCryptoSymbols(): array
    {
        // Implement a multi-layer fallback strategy
        $cachedSymbols = Cache::get('top_crypto_symbols');

        if ($cachedSymbols) {
            return $cachedSymbols;
        }

        // Predefined fallback symbols
        $fallbackSymbols = ['BTC', 'ETH', 'BNB', 'XRP', 'ADA', 'SOL', 'DOT', 'DOGE', 'AVAX', 'LINK'];

        try {
            $response = Http::withHeaders([
                'x-api-key' => env('LIVECOINWATCH_API_KEY'),
                'content-type' => 'application/json'
            ])->timeout(10) // Set a reasonable timeout
            ->retry(3, 100) // Retry mechanism
            ->post(env('LIVECOINWATCH_API_URL') . '/coins/list', [
                'currency' => 'USD',
                'sort' => 'rank',
                'order' => 'ascending',
                'limit' => self::MAX_CRYPTOCURRENCIES
            ]);

            if ($response->successful()) {
                $symbols = collect($response->json())
                    ->pluck('code')
                    ->take(self::MAX_CRYPTOCURRENCIES)
                    ->toArray();

                // Cache successful result
                Cache::put('top_crypto_symbols', $symbols, now()->addMinutes(self::CACHE_TTL_SYMBOLS));

                return $symbols;
            }
        } catch (\Exception $e) {
            Log::warning('Crypto symbol fetch failed: ' . $e->getMessage());
        }

        // Fallback to predefined symbols if API fails
        return $fallbackSymbols;
    }

    public function fetchTradingData()
    {
        try {
            // Prepare time labels
            $this->prepareTimeLabels();

            // Fetch current prices with error handling
            $currentPrices = $this->fetchCurrentPrices();

            // Manage historical data
            $historicalData = $this->manageHistoricalData($currentPrices);

            // Prepare chart datasets
            $this->prepareChartDatasets($historicalData, $currentPrices);

            // Dispatch event
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
        // Check cache first
        $cachedPrices = Cache::get('crypto_current_prices');
        if ($cachedPrices) {
            return $cachedPrices;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => env('LIVECOINWATCH_API_KEY'),
                'content-type' => 'application/json'
            ])->timeout(5)
                ->retry(2, 50)
                ->post(env('LIVECOINWATCH_API_URL') . '/coins/list', [
                    'currency' => 'USD',
                    'codes' => $this->cryptoSymbols,
                    'sort' => 'rank'
                ]);

            if ($response->successful()) {
                $prices = collect($response->json())
                    ->mapWithKeys(fn($coin) => [
                        $coin['code'] => ['rate' => $coin['rate'] ?? 0]
                    ])
                    ->toArray();

                Cache::put('crypto_current_prices', $prices, now()->addMinutes(self::CACHE_TTL_PRICES));
                return $prices;
            }
        } catch (\Exception $e) {
            Log::warning('Price fetch failed: ' . $e->getMessage());
        }

        // Fallback to zero prices
        return array_fill_keys($this->cryptoSymbols, ['rate' => 0]);
    }

    protected function manageHistoricalData(array $currentPrices): array
    {
        $historicalData = Cache::get('trading_historical_data',
            array_fill_keys($this->cryptoSymbols, array_fill(0, self::HISTORICAL_DATA_POINTS, 0))
        );

        foreach ($this->cryptoSymbols as $symbol) {
            $prices = $historicalData[$symbol] ?? array_fill(0, self::HISTORICAL_DATA_POINTS, 0);
            array_shift($prices);
            $newPrice = $currentPrices[$symbol]['rate'] ?? 0;
            array_push($prices, $newPrice);
            $historicalData[$symbol] = $prices;
        }

        Cache::put('trading_historical_data', $historicalData, now()->addHour());
        return $historicalData;
    }

    protected function prepareChartDatasets(array $historicalData, array $currentPrices)
    {
        $this->chartData['datasets'] = [];

        $colors = [
            ['border' => '#FFCE56', 'background' => 'rgba(255, 206, 86, 0.2)'],
            ['border' => '#36A2EB', 'background' => 'rgba(54, 162, 235, 0.2)'],
            // ... add more colors as needed
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
        // Log the full error
        Log::error('Critical error in trading chart: ' . $e->getMessage());

        // Set a user-friendly error message
        $this->errorMessage = 'Unable to load trading data. Please try again later.';

        // Fallback to minimal data
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
        return view('livewire.app.main.live-trading-chart', [
            'chartData' => $this->chartData,
            'errorMessage' => $this->errorMessage
        ]);
    }
}
