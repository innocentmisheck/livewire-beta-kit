<?php

namespace App\Livewire;

use App\Services\LiveCoinWatchService;
use Livewire\Component;

class MarketTrends extends Component
{
    public $cryptos = [];
    public $marketAverage = 0;
    public $lastUpdated = '';

    protected $liveCoinWatchService;

    public function boot(LiveCoinWatchService $liveCoinWatchService): void
    {
        $this->liveCoinWatchService = $liveCoinWatchService;
        $this->fetchMarketTrends();
    }

    public function fetchMarketTrends(): void
    {
        try {
            // Fetch top 50 coins (adjust limit as needed)
            $data = $this->liveCoinWatchService->getCurrenciesList();

            if (!empty($data)) {
                $this->cryptos = [];
                foreach ($data as $coin) {
                    // Shorten the symbol by taking only the part before underscore
                    $shortSymbol = explode('_', $coin['code'])[0];
                    $this->cryptos[$shortSymbol] = [
                        'price' => $coin['rate'] ?? 0,
                        'change' => ($coin['delta']['day'] ?? 0) * 100
                    ];
                }

                $this->marketAverage = count($this->cryptos) > 0
                    ? array_sum(array_column($this->cryptos, 'change')) / count($this->cryptos)
                    : 0;
                $this->lastUpdated = now()->format('M d, Y, h:i A');

                session()->flash('success', 'Market Trends');
            } else {
                $this->cryptos = [];
                $this->marketAverage = 0;
                $this->lastUpdated = '';
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch market trends: ' . $e->getMessage());
            $this->cryptos = [];
            $this->marketAverage = 0;
            $this->lastUpdated = '';
        }
    }

    public function render()
    {
        return view('livewire.app.main.market-trends');
    }
}
