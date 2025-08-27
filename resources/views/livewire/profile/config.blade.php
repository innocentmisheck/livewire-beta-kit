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
    public string $encryptedWalletId = '';
    public bool $isEmailVerified = false;
    public string $passwordStrength = 'Medium'; // Default value
    public bool $is2faEnabled = false; // Default assumption (no 2FA field in schema)
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

        // Load security status
        $this->loadSecurityStatus($user);

        // Check if the user has a wallet and load wallet data
        $this->wallet = Wallet::where('user_id', $user->id)->first();
        $this->hasWallet = !is_null($this->wallet);

        if ($this->hasWallet) {
            // Encrypt the wallet ID
            $key = env('ENCRYPTION_KEY'); // Ensure this is set in your .env file
            $iv = env('ENCRYPTION_IV');   // Ensure this is set in your .env file
            $truncatedWalletId = substr($this->wallet->id, 0, 8) . '...' . substr($this->wallet->wallet_id, -4);
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
     * Load security status from the user model.
     */
    private function loadSecurityStatus(User $user): void
    {
        // Email verification
        $this->isEmailVerified = !is_null($user->email_verified_at);

        // Password strength (basic heuristic based on hash length; update if you store metadata)
        $passwordHash = $user->password;
        $this->passwordStrength = $this->evaluatePasswordStrength($passwordHash);

        // 2FA status (assuming not implemented in schema; update if you have a 2FA table)
        $this->is2faEnabled = false; // Placeholder; adjust if 2FA is tracked elsewhere
    }

    /**
     * Evaluate password strength based on hash (basic example).
     */
    private function evaluatePasswordStrength(string $passwordHash): string
    {
        // Since password is hashed (e.g., bcrypt), we can't see plaintext length
        // This is a placeholder; ideally, store a strength indicator during registration
        $hashLength = strlen($passwordHash);
        if ($hashLength > 60) { // Typical bcrypt hash length is 60
            return 'Medium'; // Default assumption
        }
        return 'Weak'; // Fallback (unlikely with modern hashing)
    }

    /**
     * Encrypt the wallet ID.
     */
    private function encryptWalletId($walletId, $key, $iv): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $cipher = "aes-256-cbc";
        $encrypted = openssl_encrypt($walletId, $cipher, $key, 0, $iv);
        return base64_encode($encrypted);
    }

    /**
     * Load wallet data.
     */
    private function loadWalletData(): void
    {
        $holdings = Wallet::where('user_id', Auth::id())
            ->get()
            ->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'crypto' => $wallet->crypto,
                    'price' => $wallet->price,
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
     * Load recent transactions from the database.
     */
    private function loadRecentTransactions(): void
    {
        $this->recentTransactions = Transaction::where('user_id', Auth::id())
            ->where('wallet_id', $this->wallet->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'currency' => $transaction->currency,
                    'crypto' => $transaction->crypto,
                    'amount' => $transaction->amount,
                    'price' => $transaction->price,
                    'total' => $transaction->dollar,
                    'date' => $transaction->created_at->format('M d, Y H:i'),
                    'status' => $transaction->status,
                ];
            })
            ->toArray();
    }

    /**
     * Fetch current market data from LiveCoinWatch API.
     */
    private function fetchMarketData(): void
    {
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

        $this->marketData = $this->liveCoinWatchService->getCoinDetails($currencies);
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

<section class="w-full" x-data="{ isLoading: true }" x-init="setTimeout(() => isLoading = false, 0)">
    <!-- Dynamic Greeting -->
    <livewire:greeting />
    @include('partials.wallet-heading')

        <div 
        x-data="{ isLoading: true }" 
        x-init="setTimeout(() => isLoading = false, 0)"
        x-show="isLoading" 
        class="fixed inset-0 flex justify-center items-center bg-white bg-opacity-0 z-50" 
        x-transition.opacity
        >
        <div class="w-6 h-6 border-3 border-t-transparent top-0 border-green-500 dark:border-green-400 rounded-full animate-spin transition-opacity duration-1000"></div>
        </div>

   
    <div class="w-full dark:text-gray-600">
        <!-- Wallet Overview Section -->
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 shadow-lg relative">
            <div class="flex items-center mb-4">
                <x-app-logo class="h-8 w-8 mr-4"/>
                <div class="flex flex-col left-0">
                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                        <span
                            class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                        >
                            {{ auth()->user()->initials() }}
                        </span>
                    </span>
                </div>
            </div>
            

            @if ($hasWallet)
                <flux:subheading class="text-sm text-gray-600 mb-6">{{ __('Your current wallet balance and cryptocurrency holdings.') }}</flux:subheading>

             <!-- Balance Card -->
                <div class="bg-gradient-to-r from-green-500 to-green-700 rounded-xl p-6 text-white mb-6">
                    <div class="flex justify-between items-center">
                        <div class="mb-4 lg:mb-0">
                            <h3 class="text-sm font-medium opacity-80">{{ __('Total Balance') }}</h3>
                            <div class="text-3xl font-bold mt-2">${{ $totalBalanceUSD }}</div>
                        </div>
                        <div class="text-left">
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
                        </div>
                    </div>
                    <div class="mt-6 block md:flex-wrap sm:fle-wrap lg:flex lg:flex-wrap gap-3 justify-between">
                        <!-- Deposit Button -->
                        <a href="/wallet/deposit" class="flex lg:flex items-center justify-center gap-2 bg-white text-green-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v10.586l3.293-3.293a1 1 0 111.414 1.414l-5 5a1 1 0 01-1.414 0l-5-5a1 1 0 111.414-1.414L9 13.586V3a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Buy') }}
                        </a>
                        
                        <!-- Withdraw Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-red-600 text-white bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-white" fill="#FFF" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 18a1 1 0 01-1-1V6.414L5.707 9.707a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0l5 5a1 1 0 01-1.414 1.414L11 6.414V17a1 1 0 01-1 1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Sell') }}
                        </a>
                        
                        <!-- Trade Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-white text-blue-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4 3a1 1 0 011 1v10a1 1 0 102 0V5a1 1 0 112 0v6a1 1 0 102 0V3a1 1 0 112 0v8a1 1 0 102 0V5a1 1 0 112 0v6a1 1 0 102 0V4a1 1 0 011-1h1v14H3V3h1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Trade') }}
                        </a>
                        
                        <!-- Swap Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-white text-purple-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4 5a1 1 0 011-1h6a1 1 0 110 2H6.414l5.293 5.293a1 1 0 11-1.414 1.414L5 7.414V13a1 1 0 11-2 0V5a1 1 0 011-1zm12 10a1 1 0 01-1 1h-6a1 1 0 110-2h4.586l-5.293-5.293a1 1 0 111.414-1.414L15 12.586V7a1 1 0 112 0v8z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Swap') }}
                        </a>
                        
                        <!-- Stake Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-white text-yellow-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1.586l2.293-2.293a1 1 0 111.414 1.414L11 7.414V17a1 1 0 11-2 0V7.414L5.707 4.707a1 1 0 011.414-1.414L9 5.586V3a1 1 0 011-1zM5 11a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm0 4a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Stake') }}
                        </a>
                        
                        <!-- Send Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-white text-green-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 01.707.293l7 7a1 1 0 010 1.414l-7 7a1 1 0 01-1.414-1.414L15.586 10 9.293 3.707A1 1 0 0110 2zm-4 8a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('Send') }}
                        </a>
                        
                        <!-- History Button -->
                        <a href="#" class="flex lg:flex items-center justify-center gap-2 bg-white text-gray-500 bg-opacity-20 px-4 py-2 rounded-lg text-sm font-medium hover:bg-opacity-30 transition-colors w-auto lg:w-auto mb-3 lg:mb-0">
                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 2a6 6 0 11-4.243 1.757 6 6 0 014.243-1.757zM9 6a1 1 0 112 0v4a1 1 0 01-.293.707l-2 2a1 1 0 01-1.414-1.414L9 9.414V6z" clip-rule="evenodd"/>
                            </svg>
                            {{ __('History') }}
                        </a>
                    </div>
                </div>
                <!-- Asset List -->
                <div class="mt-8">
                    <flux:heading class="text-lg font-semibold mb-4">{{ __('Assets') }}</flux:heading>

                    @if(count($walletData) > 0 && count($marketData) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
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
                                                <span class="font-medium dark:text-gray-500">{{ number_format($data['amount'], 2) }}</span>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap text-right">
                                                <span class="font-medium dark:text-gray-500">${{ number_format($marketData[$currency]['price'], 2) }}</span>
                                            </td>
                                            <td class="py-4 px-4 whitespace-nowrap dark:text-green-500 text-right font-medium">
                                                ${{ number_format($data['amount'] * $marketData[$currency]['price'], 2) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-red-700 border-l-4 border-red-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-white">
                                        {{ __('Your wallet has been created but you don\'t have connection to wallet.') }}
                                    </p>
                                </div>
                            </div>
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
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $is2faEnabled ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $is2faEnabled ? __('Enabled') : __('Not Enabled') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">{{ __('Email Verification') }}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $isEmailVerified ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $isEmailVerified ? __('Verified') : __('Not Verified') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">{{ __('Password Strength') }}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $passwordStrength === 'Strong' ? 'bg-green-100 text-green-800' : 
                                    ($passwordStrength === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ __($passwordStrength) }}
                                </span>
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
                        <h4 class="text-lg font-semibold text-gray-700 dark:text-white">{{ __('Transactions') }}</h4>
                        <a href="#" class="text-sm text-green-500 hover:text-green-700">{{ __('View All') }}</a>
                    </div>

                    @if(count($recentTransactions) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white border border-gray-200 rounded-lg overflow-hidden">
                                <thead>
                                <tr>
                                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                    <th class="py-3 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Amount') }}</th>
                                    <th class="py-3 px-4 border-b text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Price') }}</th>
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
                                            <span class="font-medium dark:text-gray-500">{{ $tx['currency'] }} {{ number_format($tx['amount'], 2) }} </span>
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap text-center dark:text-gray-500">
                                            {{ $tx['crypto'] }} {{ number_format($tx['price'], 2) }}
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap text-right font-medium dark:text-green-500">
                                            ${{ number_format($tx['total'], 2) }}
                                        </td>
                                        <td class="py-4 px-4 whitespace-nowrap text-right text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($tx['date'])->diffForHumans() }}
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
                <p class="text-sm text-red-600 mt-2">{{ __('You don\'t have a connected wallet') }}</p>

                <div class="mt-4">
                    <a href="/wallet/create" class="inline-block bg-green-500 text-white px-4 py-2 rounded-lg shadow-md hover:bg-green-600">
                        {{ __('Create Wallet') }}
                    </a>
                    <a href="#" class="ml-4 inline-block bg-gray-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-gray-700">
                        {{ __('Connect Wallet') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

</section>
