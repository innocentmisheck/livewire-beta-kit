<?php

use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\LiveCoinWatchService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public bool $hasWallet = false;
    public ?string $selectedCurrency = null;
    public ?string $fiatCurrency = null;
    public array $currencies = [];
    public array $fiatCurrencies = [];
    public array $fiatCurrencyFlags = [];
    public string $currencySearch = '';
    public float $depositAmount = 0.0;
    public float $usdAmount = 0.0;
    public float $realTimeDollarRate = 0.0;
    public float $convertedAmount = 0.0;
    public float $minimumFiatAmount = 0.0;

    protected $listeners = ['currencySelected'];

    public function mount(LiveCoinWatchService $liveCoinWatchService): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->hasWallet = Wallet::where('user_id', $user->id)->exists();
        

        $this->fetchLiveCurrencies($liveCoinWatchService);
        $this->fetchFiatCurrencies();

        if ($this->hasWallet) {
            $walletCurrency = Wallet::where('user_id', $user->id)->first()->currency;
            $this->selectedCurrency = $walletCurrency;
            $this->currencySelected($walletCurrency);
        }
    }

    public function currencySelected($code): void
    {
        $this->selectedCurrency = $code;
        $this->updateRealTimeRate();
        if ($this->fiatCurrency) {
            $this->updateMinimumFiatAmount();
            $this->depositAmount = $this->minimumFiatAmount;
            $this->updateUsdAmount();
            $this->updateConvertedAmount();
        }
    }

    public function updatedFiatCurrency($value): void
    {
        $this->fiatCurrency = $value;
        $this->updateMinimumFiatAmount();
        $this->depositAmount = $this->minimumFiatAmount;
        $this->updateUsdAmount();
        $this->updateConvertedAmount();
    }

    public function updatedDepositAmount($value): void
    {
        $this->depositAmount = floatval($value);
        if ($this->depositAmount < $this->minimumFiatAmount) {
            $this->depositAmount = $this->minimumFiatAmount;
        }
        $this->updateUsdAmount();
        $this->updateConvertedAmount();
    }

    public function updatedCurrencySearch(): void
    {
        $this->dispatch('search-updated', search: $this->currencySearch);
    }

    private function fetchLiveCurrencies(LiveCoinWatchService $liveCoinWatchService): void
    {
        $currencies = $liveCoinWatchService->getCurrenciesList();
        $this->currencies = collect($currencies)->mapWithKeys(fn($coin) => [
            $coin['code'] => [
                'name' => $coin['name'],
                'icon' => $coin['png64'] ?? $coin['webp64'] ?? '',
                'rank' => $coin['rank'] ?? 0,
                'rate' => floatval($coin['rate'] ?? 0),
                'volume' => $coin['volume'] ?? 0,
            ]
        ])->toArray();
    }

    private function fetchFiatCurrencies(): void
    {
        $apiKey = config('services.exchangerate.key');
        $apiUrl = rtrim(config('services.exchangerate.url'), '/') . "/{$apiKey}/latest/USD";
        $allowedCurrencies = ['USD', 'EUR', 'GBP', 'MWK', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'BRL'];

        $this->fiatCurrencies = Cache::remember('fiat_currencies', 3600, function () use ($apiUrl, $allowedCurrencies) {
            try {
                $response = Http::retry(3, 1000)->timeout(10)->get($apiUrl);
                if ($response->successful()) {
                    return array_intersect(
                        array_keys($response->json()['conversion_rates'] ?? []),
                        $allowedCurrencies
                    );
                }
                \Log::warning('ExchangeRate-API failed: ' . $response->status());
                return $allowedCurrencies; // Fallback
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                \Log::error('Network error fetching fiat currencies: ' . $e->getMessage());
                return $allowedCurrencies; // Fallback
            } catch (\Exception $e) {
                \Log::error('Unexpected error fetching fiat currencies: ' . $e->getMessage());
                return $allowedCurrencies; // Fallback
            }
        });

        if (!in_array('USD', $this->fiatCurrencies)) {
            array_unshift($this->fiatCurrencies, 'USD');
        }

        $this->fetchCurrencyFlagsFromRestCountries();
    }

    private function fetchCurrencyFlagsFromRestCountries(): void
    {
        $this->fiatCurrencyFlags = Cache::remember('fiat_currency_flags', 86400, function () {
            $apiUrl = config('services.restcountries.url');
            try {
                $response = Http::retry(3, 1000)->timeout(10)->get($apiUrl, ['fields' => 'currencies,flags,cca2']);
                $flags = [];

                if ($response->successful()) {
                    $countries = $response->json();
                    $primaryCountries = [
                        'USD' => 'US', 'EUR' => 'FR', 'GBP' => 'GB', 'MWK' => 'MW', 'CAD' => 'CA',
                        'AUD' => 'AU', 'CHF' => 'CH', 'CNY' => 'CN', 'INR' => 'IN', 'BRL' => 'BR',
                    ];

                    foreach ($countries as $country) {
                        $currencies = array_keys($country['currencies'] ?? []);
                        $flagUrl = $country['flags']['png'] ?? $country['flags']['svg'] ?? null;
                        $countryCode = $country['cca2'] ?? null;

                        if ($flagUrl && $countryCode) {
                            foreach ($currencies as $currency) {
                                if (in_array($currency, $this->fiatCurrencies) &&
                                    isset($primaryCountries[$currency]) &&
                                    $primaryCountries[$currency] === $countryCode &&
                                    !isset($flags[$currency])) {
                                    $flags[$currency] = $flagUrl;
                                }
                            }
                        }
                    }

                    foreach ($countries as $country) {
                        $currencies = array_keys($country['currencies'] ?? []);
                        $flagUrl = $country['flags']['png'] ?? $country['flags']['svg'] ?? null;

                        if ($flagUrl) {
                            foreach ($currencies as $currency) {
                                if (in_array($currency, $this->fiatCurrencies) && !isset($flags[$currency])) {
                                    $flags[$currency] = $flagUrl;
                                }
                            }
                        }
                    }

                    if (in_array('USD', $this->fiatCurrencies)) {
                        $flags['USD'] = '🇺🇸';
                    }
                }

                foreach ($this->fiatCurrencies as $currency) {
                    $flags[$currency] = $flags[$currency] ?? 'https://via.placeholder.com/16x12?text=' . $currency;
                }

                return $flags;
            } catch (\Exception $e) {
                \Log::error('Failed to fetch currency flags: ' . $e->getMessage());
                return array_fill_keys($this->fiatCurrencies, 'https://via.placeholder.com/16x12');
            }
        });
    }

    private function updateRealTimeRate(): void
    {
        if ($this->selectedCurrency && isset($this->currencies[$this->selectedCurrency])) {
            $this->realTimeDollarRate = floatval($this->currencies[$this->selectedCurrency]['rate']) ?: 0.0;
        } else {
            $this->realTimeDollarRate = 0.0;
        }
    }

    private function updateUsdAmount(): void
    {
        if (!$this->fiatCurrency || $this->depositAmount <= 0) {
            $this->usdAmount = 0.0;
            return;
        }

        if ($this->fiatCurrency === 'USD') {
            $this->usdAmount = $this->depositAmount;
            return;
        }

        $apiKey = config('services.exchangerate.key');
        $apiUrl = rtrim(config('services.exchangerate.url'), '/') . "/{$apiKey}/latest/{$this->fiatCurrency}";

        try {
            $response = Http::retry(3, 1000)->timeout(10)->get($apiUrl);
            if ($response->successful()) {
                $rate = floatval($response->json()['conversion_rates']['USD'] ?? 0);
                $this->usdAmount = $rate > 0 ? $this->depositAmount * $rate : 0.0;
            } else {
                \Log::warning('Exchange rate fetch failed: ' . $response->status());
                $this->usdAmount = 0.0;
                session()->flash('notification', ['type' => 'error', 'message' => __('Unable to fetch exchange rates. Please try again later.')]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Network error fetching exchange rates: ' . $e->getMessage());
            $this->usdAmount = 0.0;
            session()->flash('notification', ['type' => 'error', 'message' => __('Network error. Please check your connection and try again.')]);
        } catch (\Exception $e) {
            \Log::error('Unexpected error fetching exchange rates: ' . $e->getMessage());
            $this->usdAmount = 0.0;
            session()->flash('notification', ['type' => 'error', 'message' => __('An unexpected error occurred. Please try again.')]);
        }
    }

    private function updateConvertedAmount(): void
    {
        $this->convertedAmount = ($this->realTimeDollarRate > 0 && $this->usdAmount > 0)
            ? $this->usdAmount / $this->realTimeDollarRate
            : 0.0;
    }

    private function updateMinimumFiatAmount(): void
    {
        if (!$this->selectedCurrency || !$this->fiatCurrency || $this->realTimeDollarRate <= 0) {
            $this->minimumFiatAmount = 0.0;
            return;
        }

        $cryptoPriceInUsd = $this->realTimeDollarRate;

        if ($this->fiatCurrency === 'USD') {
            $this->minimumFiatAmount = $cryptoPriceInUsd;
            return;
        }

        $apiKey = config('services.exchangerate.key');
        $apiUrl = rtrim(config('services.exchangerate.url'), '/') . "/{$apiKey}/latest/USD";

        try {
            $response = Http::retry(3, 1000)->timeout(10)->get($apiUrl);
            if ($response->successful()) {
                $usdToFiatRate = floatval($response->json()['conversion_rates'][$this->fiatCurrency] ?? 0);
                $this->minimumFiatAmount = $usdToFiatRate > 0 ? $cryptoPriceInUsd * $usdToFiatRate : 0.0;
            } else {
                \Log::warning('Failed to fetch minimum fiat amount: ' . $response->status());
                $this->minimumFiatAmount = 0.0;
                session()->flash('notification', ['type' => 'error', 'message' => __('Unable to fetch exchange rates.')]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            \Log::error('Network error in updateMinimumFiatAmount: ' . $e->getMessage());
            $this->minimumFiatAmount = 0.0;
            session()->flash('notification', ['type' => 'error', 'message' => __('You are offline. Please try again.')]);
        } catch (\Exception $e) {
            \Log::error('Unexpected error in updateMinimumFiatAmount: ' . $e->getMessage());
            $this->minimumFiatAmount = 0.0;
            session()->flash('notification', ['type' => 'error', 'message' => __('An error occurred. Please try again.')]);
        }
    }

    public function depositToWallet()
    {
        $user = Auth::user();

        if (!$this->hasWallet) {
            session()->flash('notification', ['type' => 'error', 'message' => __('Please create a wallet first.')]);
            return;
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$this->selectedCurrency || !$this->fiatCurrency || $this->depositAmount < $this->minimumFiatAmount || $this->realTimeDollarRate <= 0 || $this->usdAmount <= 0) {
            session()->flash('notification', ['type' => 'error', 'message' => __('Enter currencies and enter a valid amount.')]);
            return;
        }

        if ($this->selectedCurrency !== $wallet->currency) {
            session()->flash('notification', ['type' => 'error', 'message' => __('Crypto currency must match wallet currency.')]);
            return;
        }

        $this->updateRealTimeRate();
        $this->updateUsdAmount();
        $this->updateConvertedAmount();

        if ($this->realTimeDollarRate <= 0 || $this->usdAmount <= 0) {
            session()->flash('notification', ['type' => 'error', 'message' => __('Unable to fetch real-time rates.')]);
            return;
        }

        $transaction = Transaction::create([
            'user_id' => $user->id, // Ensure this is an integer if the users table uses integers
            'wallet_id' => $wallet->id, // Ensure this is a UUID if the wallets table uses UUIDs
            'type' => 'deposit',
            'price' => $this->convertedAmount,
            'amount' => $this->minimumFiatAmount,
            'crypto' => $this->selectedCurrency,
            'currency' => $this->fiatCurrency,
            'rate' => $this->realTimeDollarRate,
            'dollar' => $this->usdAmount,
            'status' => 'pending',
        ]);


        // Redirect to checkout page with transaction ID
        return redirect()->route('profile.wallet.checkout', ['id' => $transaction->id]);

        // $wallet->increment('amount', $this->convertedAmount);

        // session()->flash('notification', ['type' => 'success', 'message' => __('Deposit successful.')]);

        $this->selectedCurrency = null;
        $this->fiatCurrency = null;
        $this->depositAmount = 0.0;
        $this->usdAmount = 0.0;
        $this->convertedAmount = 0.0;
        $this->minimumFiatAmount = 0.0;
    }
};
?>


<section class="w-full" x-data="{ isLoading: true }" x-init="setTimeout(() => isLoading = false, 0)">
    <livewire:greeting />
    @include('partials.buy.buy-crypto-heading')
    @include('partials.notification')


  <!-- Loading Spinner (Moved outside the section to cover the whole screen) -->
        <div 
        x-data="{ isLoading: true }" 
        x-init="setTimeout(() => isLoading = false, 0)"
        x-show="isLoading" 
        class="fixed inset-0 flex justify-center items-center bg-white bg-opacity-0 z-50 dark:bg-neutral-900 dark:bg-opacity-90" 
        x-transition.opacity
        >
        <div class="w-6 h-6 border-3 border-t-transparent top-0 border-green-500 dark:border-green-400 rounded-full animate-spin transition-opacity duration-1000"></div>
        </div>


    <!-- Main Content -->
    <div class="container max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mt-5" x-show="!isLoading" x-transition.opacity>
        

        <div class="container">
            @if (session()->has('notification'))
                <div class="flex items-center mb-4 p-4 rounded-md {{ session('notification.type') === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ session('notification.type') === 'success' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}" />
                    </svg>
                    {{ session('notification.message') }}
                </div>
            @endif

            @if($hasWallet)
            <form wire:submit.prevent="depositToWallet" wire.polls.60s="mount" wire.mount="mount" class="space-y-4">

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                    <!-- Crypto Currency Selector -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-1 dark:text-white">{{ __('Crypto') }}</label>
                        <div 
                            x-data="{
                                open: false, 
                                search: '', 
                                selectedCode: @entangle('selectedCurrency').live, 
                                selectCurrency(code) { 
                                    this.selectedCode = code; 
                                    this.open = false; 
                                    this.search = ''; 
                                    $wire.call('currencySelected', code); 
                                }
                            }" 
                            class="relative w-full border border-gray-300  rounded-md shadow-md dark:text-black" 
                            @keydown.escape="open = false"
                            x-init="selectedCode = @json($selectedCurrency)"
                        >
                            <div @click="open = !open" class="w-full p-3 border border-gray-300 rounded-md cursor-pointer flex items-center justify-between dark:" :class="{'ring-2 ring-green-500 border-transparent': open}">
                                <span class="flex items-center">
                                    <template x-if="selectedCode && $wire.currencies[selectedCode]">
                                        <div class="flex items-center">
                                            <img :src="$wire.currencies[selectedCode].icon" :alt="selectedCode" class="w-6 h-6 mr-2 rounded-full">
                                            <span class="dark:text-white" x-text="$wire.currencies[selectedCode].name"></span>
                                        </div>
                                    </template>
                                    <template x-if="!selectedCode"><span>{{ __('Select a crypto') }}</span></template>
                                </span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div x-show="open" @click.away="open = false" class="absolute w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto" x-transition>
                                <div class="p-2 sticky top-0 bg-white">
                                    <input x-model="search" type="text" placeholder="Search..." class="w-full p-2 border border-gray-500 rounded-md focus:border-gray-500 focus:ring-0 focus:outline-none" />
                                </div>
                                <template x-for="(currency, code) in $wire.currencies" :key="code">
                                    <div x-show="!search || currency.name.toLowerCase().includes(search.toLowerCase()) || code.toLowerCase().includes(search.toLowerCase())" @click="selectCurrency(code)" class="flex items-center p-3 hover:bg-gray-100 cursor-pointer" :class="{'bg-gray-100': selectedCode === code}">
                                        <img :src="currency.icon" :alt="code" class="w-6 h-6 mr-2 rounded-full">
                                        <div>
                                            <span class="dark:text-black" x-text="currency.name"></span>
                                            <div class="text-xs text-gray-500" x-text="'Rank: ' + currency.rank + ' • $' + currency.rate.toFixed(2)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Fiat Currency Selector with REST Countries Flags -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-1 dark:text-white">{{ __('Currency') }}</label>
                        <div 
                            x-data="{
                                fiatOpen: false, 
                                fiatSearch: '', 
                                fiatSelectedCode: @entangle('fiatCurrency').live, 
                                selectFiat(code) { 
                                    this.fiatSelectedCode = code; 
                                    this.fiatOpen = false; 
                                    this.fiatSearch = ''; 
                                    $wire.set('fiatCurrency', code);
                                }
                            }" 
                            class="relative w-full border border-gray-300 rounded-md shadow-md" 
                            @keydown.escape="fiatOpen = false"
                        >
                            <div 
                                @click="fiatOpen = !fiatOpen" 
                                class="w-full p-3 border border-gray-300 rounded-md cursor-pointer flex items-center justify-between" 
                                :class="{'ring-2 ring-green-500 border-transparent': fiatOpen, 'cursor-not-allowed bg-gray-200': !$wire.selectedCurrency}" 
                                :disabled="!$wire.selectedCurrency"
                            >
                                <span class="flex items-center">
                                    <template x-if="fiatSelectedCode">
                                        <div class="flex items-center">
                                            <template x-if="$wire.fiatCurrencyFlags[fiatSelectedCode] === '🇺🇸'">
                                                <span 
                                                    x-text="'🇺🇸'" 
                                                    class="w-4 h-3 mr-3 inline-block align-middle text-center"
                                                    style="line-height: 12px;"
                                                ></span>
                                            </template>
                                            <template x-if="$wire.fiatCurrencyFlags[fiatSelectedCode] !== '🇺🇸'">
                                                <img 
                                                    :src="$wire.fiatCurrencyFlags[fiatSelectedCode]" 
                                                    :alt="fiatSelectedCode + ' flag'" 
                                                    class="w-4 h-3 mr-3 inline-block align-middle"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'"
                                                >
                                            </template>
                                            <span x-text="fiatSelectedCode"></span>
                                        </div>
                                    </template>
                                    <template x-if="!fiatSelectedCode">
                                        <span>{{ __('Select a currency') }}</span>
                                    </template>
                                </span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <div 
                                x-show="fiatOpen && $wire.selectedCurrency" 
                                @click.away="fiatOpen = false" 
                                class="absolute w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto" 
                                x-transition
                            >
                                <div class="p-2 sticky top-0 bg-white border-b border-gray-200">
                                    <input 
                                        x-model="fiatSearch" 
                                        type="text" 
                                        placeholder="Search..." 
                                        class="w-full p-2 border border-gray-500 rounded-md focus:border-gray-500 focus:ring-0 focus:outline-none dark:text-gray-500" 
                                    />
                                </div>
                                <template x-for="code in $wire.fiatCurrencies" :key="code">
                                    <div 
                                        x-show="!fiatSearch || code.toLowerCase().includes(fiatSearch.toLowerCase())" 
                                        @click="selectFiat(code)" 
                                        class="p-3 hover:bg-gray-100 cursor-pointer flex items-center dark:text-gray-500" 
                                        :class="{'bg-gray-200': fiatSelectedCode === code}"
                                    >
                                        <template x-if="$wire.fiatCurrencyFlags[code] === '🇺🇸'">
                                            <span 
                                                x-text="'🇺🇸'" 
                                                class="w-4 h-3 mr-2 inline-block align-middle text-center"
                                                style="line-height: 12px;"
                                            ></span>
                                        </template>
                                        <template x-if="$wire.fiatCurrencyFlags[code] !== '🇺🇸'">
                                            <img 
                                                :src="$wire.fiatCurrencyFlags[code]" 
                                                :alt="code + ' flag'" 
                                                class="w-4 h-3 mr-2 inline-block align-middle"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='inline'"
                                            >
                                        </template>
                                        <span x-text="code"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                   <!-- Deposit Amount Input -->
                    <div>
                        <label class="block font-medium text-gray-700 mb-1 dark:text-white">{{ __('Amount in') }} {{ $fiatCurrency }}</label>
                        <div 
                            x-data="{
                                formattedAmount: '',
                                rawAmount: @entangle('depositAmount').live,
                                minimumFiat: @entangle('minimumFiatAmount').live,
                                formatNumber(value) {
                                    if (!value && value !== 0) return '';
                                    const num = parseFloat(value.toString().replace(/[^0-9.]/g, ''));
                                    return isNaN(num) ? '' : num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                },
                                parseInput(value) {
                                    // Allow typing with numbers and decimals, strip other characters
                                    const cleaned = value.replace(/[^0-9.]/g, '');
                                    const num = parseFloat(cleaned) || 0;
                                    return num;
                                },
                                updateRawAmount(value) {
                                    const raw = this.parseInput(value);
                                    this.rawAmount = raw;
                                    $wire.set('depositAmount', raw);
                                },
                                enforceMinimum() {
                                    if (this.rawAmount < this.minimumFiat) {
                                        this.rawAmount = this.minimumFiat;
                                        this.formattedAmount = this.formatNumber(this.minimumFiat);
                                        $wire.set('depositAmount', this.minimumFiat);
                                    } else {
                                        this.formattedAmount = this.formatNumber(this.rawAmount);
                                    }
                                }
                            }"
                            x-init="formattedAmount = formatNumber(rawAmount); $watch('rawAmount', value => formattedAmount = formatNumber(value))"
                        >
                            <input 
                                type="text" 
                                x-model="formattedAmount" 
                                @input="updateRawAmount($event.target.value)" 
                                @blur="enforceMinimum()" 
                                class="w-full p-3 border border-gray-300 rounded-md focus:border-green-500 focus:ring-0 focus:outline-none" 
                                :disabled="!$wire.fiatCurrency" 
                                :min="$wire.minimumFiatAmount" 
                                placeholder="Enter amount"
                            >
                            <template x-if="$wire.minimumFiatAmount > 0">
                                <span class="text-sm text-gray-600 dark:text-white" x-text="'Minimum (1 ' + $wire.selectedCurrency + '): ' + formatNumber($wire.minimumFiatAmount) + ' ' + $wire.fiatCurrency"></span>
                            </template>
                            @error('depositAmount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Real-time Conversion Display -->
                <div 
                    x-show="$wire.selectedCurrency && $wire.fiatCurrency && $wire.depositAmount > 0" 
                    x-data="{
                        usd: @entangle('usdAmount').live, 
                        converted: @entangle('convertedAmount').live, 
                        rate: @entangle('realTimeDollarRate').live,
                        formatNumber(value, decimals) {
                            if (!value) return '0.00';
                            return Number(value).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                        }
                    }" 
                    class="bg-gray-100 p-4 rounded-md" 
                    x-transition.opacity
                >
                    <p class="text-sm text-gray-600">USD: $<span x-text="formatNumber(usd, 2)"></span></p>
                    <p class="text-sm text-gray-600">1 <span x-text="$wire.selectedCurrency"></span> = $<span x-text="formatNumber(rate, 4)"></span></p>
                    <p class="text-lg font-medium dark:text-black">Total: <span x-text="formatNumber(converted, 8)"></span> <span x-text="$wire.selectedCurrency"></span></p>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="bg-green-500 text-white mt-5 px-2 py-2 rounded-lg shadow-md flex items-end text-end justify-end gap-2 hover:bg-gray-950 disabled:bg-gray-400 disabled:cursor-not-allowed" 
                    :hidden="!$wire.selectedCurrency || !$wire.fiatCurrency || !$wire.depositAmount || $wire.depositAmount < $wire.minimumFiatAmount || $wire.realTimeDollarRate <= 0 || $wire.usdAmount <= 0"
                >
                    {{ __('Continue') }}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12m-4-4l4 4m-4 4l4-4" />
                    </svg>
                </button>
            </form>
            @else
                <p class="text-sm text-red-600 mt-2">{{ __('You don\'t have a connected wallet.') }}</p>
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

