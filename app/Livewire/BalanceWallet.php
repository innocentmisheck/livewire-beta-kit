<?php

namespace App\Livewire;

use App\Services\LiveCoinWatchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BalanceWallet extends Component
{
    public $walletBalance = 'N/A';
    public $percentageChange = 'N/A';
    public $btcAmount = 'N/A';
    public $lastUpdated;
    public $walletCurrency = 'BTC';
    public $walletIcon;

    public function boot(LiveCoinWatchService $liveCoinWatchService): void
    {
     
        $this->fetchWalletData($liveCoinWatchService);
    }

    public function fetchWalletData(?LiveCoinWatchService $liveCoinWatchService = null): void
    {
        if (!$liveCoinWatchService) {
            $liveCoinWatchService = app(LiveCoinWatchService::class);
        }

        try {
            $user = Auth::user();
            Log::info('User authenticated', ['user_id' => $user?->id]);

            $holdings = $user ? $user->wallets->pluck('amount', 'currency')->toArray() : [];
            Log::info('User holdings', ['holdings' => $holdings]);

            if (empty($holdings)) {
                $holdings = ['BTC' => 0.00];
                Log::warning('No holdings found, using default');
            }

            $currenciesList = $liveCoinWatchService->getCurrenciesList();
            $iconMap = [];
            foreach ($currenciesList as $coin) {
                if (isset($coin['code']) && isset($coin['webp64'])) {
                    $iconMap[$coin['code']] = $coin['webp64'];
                }
            }

            $currentPrices = $liveCoinWatchService->getCoinDetails(array_keys($holdings));
            Log::info('User - Current prices', ['prices' => $currentPrices]);

            if (empty($currentPrices)) {
                Log::error('Failed to get current prices');
                $this->walletBalance = 'N/A';
                $this->btcAmount = 'N/A';
                $this->percentageChange = 'N/A';
                return;
            }

            $historicalResponse = $liveCoinWatchService->getPriceHistory('BTC', 1);

            $totalValue = 0;
            foreach ($holdings as $currency => $amount) {
                $price = $currentPrices[$currency]['price'] ?? 0;
                $totalValue += $amount * $price;
            }
            $this->walletBalance = number_format($totalValue, 2);

            $btcPrice = $currentPrices['BTC']['price'] ?? 45000;
            $totalBtc = 0;
            foreach (array_keys($holdings) as $currency) {
                $coinPrice = $currentPrices[$currency]['price'] ?? 0;
                $totalBtc += ($holdings[$currency] * $coinPrice) / $btcPrice;
            }
            $this->btcAmount = number_format($totalBtc, 4);

            $latestPrice = $currentPrices['BTC']['price'] ?? 45000;
            $previousPrice = !empty($historicalResponse['datasets'][0]['data'])
                ? end($historicalResponse['datasets'][0]['data'])
                : $latestPrice;
            $this->percentageChange = !empty($historicalResponse['datasets'][0]['data'])
                ? number_format((($latestPrice - $previousPrice) / $previousPrice) * 100, 2)
                : '0.00';

            $this->walletCurrency = array_key_first($holdings);
            $this->walletIcon = $iconMap[$this->walletCurrency] ?? null;

            if (!$this->walletIcon && isset($currentPrices[$this->walletCurrency])) {
                $this->walletIcon = $currentPrices[$this->walletCurrency]['icon'] ?? null;
            }

            if (!$this->walletIcon) {
                $specificCoin = $liveCoinWatchService->getCoinDetails([$this->walletCurrency]);
                if (isset($specificCoin[$this->walletCurrency]['icon'])) {
                    $this->walletIcon = $specificCoin[$this->walletCurrency]['icon'];
                }
            }

            $this->lastUpdated = now()->format('M d, Y');
        } catch (\Exception $e) {
            Log::error('Failed to fetch wallet data: ' . $e->getMessage());
            $this->walletBalance = 'N/A';
            $this->btcAmount = 'N/A';
            $this->percentageChange = 'N/A';
            $this->lastUpdated = now()->format('M d, Y');
            $this->walletCurrency = 'BTC';
        }
    }

    public function render()
    {
        return view('livewire.auth.balance-wallet');
    }
}
