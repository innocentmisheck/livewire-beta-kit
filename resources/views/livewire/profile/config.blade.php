<?php

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\LiveCoinWatchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public bool $hasWallet = false;
    public ?Wallet $wallet = null;
    public array $walletData = [];
    public array $recentTransactions = [];
    public array $marketData = [];
    public float $totalBalance = 0;
    public string $totalBalanceUSD = '0.00';
    public array $priceChanges = [];
    public string $encryptedWalletId = ''; // Add this property
    protected $liveCoinWatchService;

    /**
     * Mount the component.
     */
    public function mount(LiveCoinWatchService $liveCoinWatchService): void
    {
        $this->liveCoinWatchService = $liveCoinWatchService;
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;

        // Check if the user has a wallet and load wallet data
        $this->wallet = Wallet::where('user_id', $user->id)->first();
        $this->hasWallet = !is_null($this->wallet);

        if ($this->hasWallet) {
            // Encrypt the wallet ID
            $key = env('ENCRYPTION_KEY'); // Ensure this is set in your .env file
            $iv = env('ENCRYPTION_IV'); // Ensure this is set in your .env file
            $truncatedWalletId = substr($this->wallet->id, 0, 8) . '...' . substr($this->wallet->id, -4);
            $encryptedWalletId = $this->encryptWalletId($truncatedWalletId, $key, $iv);

            // Truncate the encrypted wallet ID to 10 characters
            $this->encryptedWalletId = substr($encryptedWalletId, 0, 10);

            $this->loadWalletData();
            $this->loadRecentTransactions();
            $this->fetchMarketData();
            $this->calculateTotalBalance();
        }
    }

    /**
     * Encrypt the wallet ID.
     */
    private function encryptWalletId($walletId, $key, $iv): string
    {
        $cipher = "aes-256-cbc";
        $encrypted = openssl_encrypt($walletId, $cipher, $key, 0, $iv);
        return base64_encode($encrypted); // Return base64 encoded encrypted string
    }

    /**
     * Load wallet data.
     */
    private function loadWalletData(): void
    {
        // Get all wallet holdings for the user
        $holdings = Wallet::where('user_id', Auth::id())
            ->get()
            ->map(function($wallet) {
                return [
                    'id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'amount' => $wallet->amount,
                    'created_at' => $wallet->created_at->format('M d, Y'),
                    'last_updated' => $wallet->updated_at->format('M d, Y'),
                ];
            })
            ->keyBy('currency')
            ->toArray();

        $this->walletData = $holdings;
    }

    /**
     * Load recent transactions.
     */
    private function loadRecentTransactions(): void
    {
        // Mock data for recent transactions
        $this->recentTransactions = [
            [
                'id' => 1,
                'type' => 'buy',
                'currency' => $this->wallet->currency,
                'amount' => 0.05,
                'price' => 34250.00,
                'total' => 1712.50,
                'date' => now()->subDays(2)->format('M d, Y H:i'),
                'status' => 'completed'
            ],
            [
                'id' => 2,
                'type' => 'deposit',
                'currency' => 'USD',
                'amount' => 2000.00,
                'price' => 1.00,
                'total' => 2000.00,
                'date' => now()->subDays(5)->format('M d, Y H:i'),
                'status' => 'completed'
            ]
        ];
    }

    /**
     * Fetch current market data from LiveCoinWatch API.
     */
    private function fetchMarketData(): void
    {
        // Get all currencies in the user's wallet
        $currencies = array_keys($this->walletData);

        if (empty($currencies)) {
            $this->marketData = [];
            $this->priceChanges = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Price',
                        'data' => [],
                        'borderColor' => '#10B981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    ]
                ]
            ];
            return;
        }

        // Get coin details from LiveCoinWatch
        $this->marketData = $this->liveCoinWatchService->getCoinDetails($currencies);

        // Get price history for the primary coin (first in the list)
        $primaryCoin = $currencies[0];
        $this->priceChanges = $this->liveCoinWatchService->getPriceHistory($primaryCoin);
    }

    /**
     * Calculate total balance in USD.
     */
    private function calculateTotalBalance(): void
    {
        $total = 0;

        foreach ($this->walletData as $currency => $data) {
            if (isset($this->marketData[$currency])) {
                $total += $data['amount'] * $this->marketData[$currency]['price'];
            }
        }

        $this->totalBalance = $total;
        $this->totalBalanceUSD = number_format($total, 2);
    }

    /**
     * Format large numbers for display.
     */
    public function formatNumber($number): string
    {
        if ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        }
        if ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }

        return number_format($number, 2);
    }
};
?>


<section class="w-full">

    <!-- Dynamic Greeting -->
    <livewire:greeting />

    @include('partials.wallet-heading')

        <div class="my-6 w-full dark:text-gray-600 space-y-6">
            <!-- Wallet Overview Section -->
            <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 shadow-lg relative">
                <div class="flex items-center mb-4">
                    <x-app-logo class="h-8 w-8 mr-2"/>
                    <flux:heading size="xl"   level="1">Overview</flux:heading>
                </div>

                @if ($hasWallet)
                    <flux:subheading class="text-sm text-gray-600 mb-6">{{ __('Your current wallet balance and cryptocurrency holdings.') }}</flux:>

                    <!-- Balance Card -->
                    <div class="bg-gradient-to-r from-green-500 to-green-700 rounded-xl p-6 text-white mb-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-medium opacity-80">{{ __('Total Balance') }}</h3>
                                <div class="text-3xl font-bold mt-1">${{ $totalBalanceUSD }}</div>
                            </div>
                            <div class="text-right">
                                @if (!empty($marketData) && count($marketData) > 0)
                                    @php
                                        $firstCurrency = array_key_first($marketData);
                                        $change24h = $marketData[$firstCurrency]['change_24h'] ?? 0;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $change24h >= 0 ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            @if ($change24h >= 0)
                                                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                                            @else
                                                <path fill-rule="evenodd" d="M12 13a1 1 0 110 2H7a1 1 0 01-1-1v-5a1 1 0 112 0v2.586l4.293-4.293a1 1 0 011.414 0L16 9.586l4.293-4.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0L13 9.414l-1 1V13z" clip-rule="evenodd" />
                                            @endif
                                        </svg>
                                        {{ $change24h >= 0 ? '+' : '' }}{{ number_format($change24h, 2) }}%
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-600 text-white">
                                        0.00%
                                    </span>
                                @endif
                                <div class="text-sm mt-1 opacity-80">{{ __('Past 24 hours') }}</div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-between space-x-2">
                            <!-- Deposit Button -->
                            <a href="#" class="flex items-center gap-2 bg-white text-green-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v10.586l3.293-3.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0l-5-5a1 1 0 111.414-1.414L9 13.586V3a1 1 0 011-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('Deposit') }}
                            </a>

                            <!-- Withdraw Button -->
                            <a href="#" class="flex items-center gap-2 bg-white text-black bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors">
                                <svg class="w-5 h-5 text-black" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M10 18a1 1 0 01-1-1V6.414L5.707 9.707a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0l5 5a1 1 0 01-1.414 1.414L11 6.414V17a1 1 0 01-1 1z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('Withdraw') }}
                            </a>

                            <!-- Trade Button -->
                            <a href="#" class="flex items-center gap-2 bg-white text-blue-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors">
                                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4 3a1 1 0 011 1v10a1 1 0 102 0V5a1 1 0 112 0v6a1 1 0 102 0V3a1 1 0 112 0v8a1 1 0 102 0V5a1 1 0 112 0v6a1 1 0 102 0V4a1 1 0 011-1h1v14H3V3h1z" clip-rule="evenodd"/>
                                </svg>
                                {{ __('Trade') }}
                            </a>
                        </div>
                    </div>

                    <!-- Asset List -->
                    <div class="mt-8">
                        <flux:heading class="text-lg font-semibold  mb-4">{{ __('Your Assets') }}</flux:heading>

                        @if(count($walletData) > 0 && count($marketData) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border border-gray-200">
                                    <thead>
                                    <tr>
                                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Asset') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Balance') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Price') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Value') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                    @foreach($walletData as $currency => $data)
                                        @if(isset($marketData[$currency]))
                                            <tr>
                                                <td class="py-4 px-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <img src="{{ $marketData[$currency]['icon'] }}" alt="{{ $currency }}" class="h-8 w-8 mr-3">
                                                        <div>
                                                            <div class="font-medium text-gray-900">{{ $marketData[$currency]['name'] }}</div>
                                                            <div class="text-gray-500 text-sm">{{ $currency }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap text-right">
                                                    <span class="font-medium">{{ number_format($data['amount'], 2) }}</span>
                                                </td>
                                                <td class="py-4 px-4 whitespace-nowrap text-right">
                                                    <span class="font-medium">${{ number_format($marketData[$currency]['price'], 2) }}</span>
                                                </td>
                                                <!-- <td class="py-4 px-4 whitespace-nowrap text-right">
                                                        <span class="px-2 py-1 rounded-full text-sm font-medium {{ $marketData[$currency]['change_24h'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ $marketData[$currency]['change_24h'] >= 0 ? '+' : '' }}{{ number_format($marketData[$currency]['change_24h'], 2) }}%
                                                        </span>
                                                </td> -->
                                                <td class="py-4 px-4 whitespace-nowrap text-right font-medium">
                                                    ${{ number_format($data['amount'] * $marketData[$currency]['price'], 2) }}
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            {{ __('Your wallet has been created but you don\'t have any assets yet.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex mt-4 items-center justify-center space-x-4">
                                <!-- Buy Button -->
                                <a href="#" class="flex items-center gap-2 bg-green-500 text-white px-4 py-2 w-full rounded-lg shadow-md hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v10.586l3.293-3.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0l-5-5a1 1 0 111.414-1.414L9 14.586V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Buy') }}
                                </a>

                                <!-- Sell Button -->
                                <a href="#" class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 w-full rounded-lg shadow-md hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M10 17a1 1 0 01-1-1V6.414L5.707 9.707a1 1 0 111.414-1.414l5-5a1 1 0 011.414 0l5 5a1 1 0 01-1.414 1.414L11 6.414V16a1 1 0 01-1 1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Sell') }}
                                </a>
                            </div>

                        @endif
                    </div>

                    <!-- Wallet Info -->
                    <div class="mt-8 grid md:grid-cols-2 gap-6">
                        <!-- Wallet Details -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">{{ __('Wallet Information') }}</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Wallet ID') }}</span>
                                    <span class="font-medium">{{ $this->encryptedWalletId }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Created On') }}</span>
                                    <span class="font-medium">{{ $wallet->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Last Updated') }}</span>
                                    <span class="font-medium">{{ $wallet->updated_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">{{ __('Default Currency') }}</span>
                                    <span class="font-medium">{{ $wallet->currency }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Security Status -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-semibold text-gray-700 mb-3">{{ __('Security Status') }}</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">{{ __('2FA Authentication') }}</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('Not Enabled') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">{{ __('Email Verification') }}</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('Verified') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">{{ __('Password Strength') }}</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('Medium') }}</span>
                                </div>

                                <a href="/settings/profile" class="block mt-2 text-center text-sm text-green-500 hover:text-green-700">
                                    {{ __('Advance Security Settings') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-semibold text-gray-700">{{ __('Recent Transactions') }}</h4>
                            <a href="#" class="text-sm text-green-500 hover:text-green-700">{{ __('View All') }}</a>
                        </div>

                        @if(count($recentTransactions) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                                    <thead>
                                    <tr>
                                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                        <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Price') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Total') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                        <th class="py-3 px-4 border-b text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                    @foreach($recentTransactions as $tx)
                                        <tr>
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tx['type'] == 'buy' ? 'bg-green-100 text-green-800' : ($tx['type'] == 'sell' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                                        {{ ucfirst($tx['type']) }}
                                                    </span>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap">
                                                <span class="font-medium">{{ number_format($tx['amount'], 8) }} {{ $tx['currency'] }}</span>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap text-right">
                                                ${{ number_format($tx['price'], 2) }}
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap text-right font-medium">
                                                ${{ number_format($tx['total'], 2) }}
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap text-right text-sm text-gray-500">
                                                {{ $tx['date'] }}
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap text-right">
                                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tx['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                        {{ ucfirst($tx['status']) }}
                                                    </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
                                {{ __('No recent transactions found.') }}
                            </div>
                        @endif
                    </div>

                @else
                    <p class="text-sm text-red-600 mt-2">{{ __('You don\'t have a connected wallet. Please connect or create a new wallet to manage your funds.') }}</p>

                    <div class="mt-4">
                        <a href="{{ route('profile.create-wallet') }}" class="inline-block bg-green-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-green-600">
                            {{ __('Create Wallet') }}
                        </a>
                        <a href="#" class="ml-4 inline-block bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-gray-700">
                            {{ __('Connect Wallet') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

    @include('partials.footer')
</section>
