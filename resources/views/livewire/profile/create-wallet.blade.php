
<?php

use App\Models\Wallet;
use App\Services\LiveCoinWatchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public bool $hasWallet = false;
    public ?string $selectedCurrency = null;
    public array $currencies = [];
    public string $currencySearch = '';

    // Add watchers for synchronized state
    protected $listeners = ['currencySelected'];

    public function mount(LiveCoinWatchService $liveCoinWatchService): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->hasWallet = Wallet::where('user_id', $user->id)->exists();

        // Fetch currencies using the LiveCoinWatchService
        $this->fetchLiveCurrencies($liveCoinWatchService);
    }

    // Add a dedicated method for currency selection
    public function currencySelected($code): void
    {
        $this->selectedCurrency = $code;
    }

    // Add a dedicated method for search updates
    public function updatedCurrencySearch(): void
    {
        // This method will automatically be called when currencySearch is updated
        $this->dispatch('search-updated', search: $this->currencySearch);
    }

    private function fetchLiveCurrencies(LiveCoinWatchService $liveCoinWatchService): void
    {
        // Fetch currencies from the service
        $currencies = $liveCoinWatchService->getCurrenciesList();

        // Format the currencies array
        $this->currencies = collect($currencies)->mapWithKeys(fn($coin) => [
            $coin['code'] => [
                'name' => $coin['name'],
                'icon' => $coin['png64'] ?? '',
                'rank' => $coin['rank'] ?? 0,
                'rate' => $coin['rate'] ?? 0,
                'volume' => $coin['volume'] ?? 0,
            ]
        ])->toArray();
    }

    public function createWallet(): void
    {
        $user = Auth::user();

        if ($this->hasWallet) {
            session()->flash('notification', ['type' => 'error', 'message' => __('You already have a wallet.')]);
            return;
        }

        // Validate that a currency is selected
        if (!$this->selectedCurrency) {
            session()->flash('notification', ['type' => 'error', 'message' => __('Please select a currency first.')]);
            return;
        }

        // Create the wallet
        Wallet::create([
            'user_id'   => $user->id,
            'currency'  => $this->selectedCurrency,
            'amount'    => 0.00000000,
        ]);

        // Set success notification
        session()->flash('notification', ['type' => 'success', 'message' => __('Wallet created successfully.')]);

        // Update the hasWallet property
        $this->hasWallet = true;

        // Redirect to the dashboard
        $this->redirect('/');
    }
};
?>
    <!-- Blade Template -->
<section class="w-full">
    <livewire:greeting />
    @include('partials.wallet-heading')
    @include('partials.notification')

    <div class="container mx-auto">
        <h2 class="text-2xl font-bold mb-4">{{ __('Create Wallet') }}</h2>

        @if (session()->has('notification'))
            <div class="flex items-center mb-4 p-4 rounded-md {{ session('notification.type') === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white' }}">
                @if (session('notification.type') === 'success')
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @else
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                @endif
                {{ session('notification.message') }}
            </div>
        @endif

        @if(!$hasWallet)
            <form wire:submit.prevent="createWallet" class="space-y-1">
                <!-- Custom Currency Selector with Alpine.js - Improved Version -->
                <label class="block font-medium text-gray-700">{{ __('Select Currency') }}</label>
                <div
                    x-data="{
                        open: false,
                        search: '',
                        selectedCode: @entangle('selectedCurrency'),

                        init() {
                            this.$watch('selectedCode', (value) => {
                                if (value) {
                                    $wire.call('currencySelected', value);
                                }
                            });
                        },

                        selectCurrency(code) {
                            this.selectedCode = code;
                            this.open = false;
                            this.search = '';
                        }
                    }"
                    class="relative w-full border border-gray-300 rounded-md shadow-md"
                    @keydown.escape="open = false"
                >
                    <!-- Dropdown Trigger -->
                    <div
                        @click="open = !open"
                        class="w-full p-3 border border-gray-300 rounded-md cursor-pointer flex items-center justify-between"
                        :class="{'ring-2 ring-green-500 border-transparent': open}"
                    >
                        <span class="flex items-center">
                            <template x-if="selectedCode && $wire.currencies[selectedCode]">
                                <div class="flex items-center">
                                    <img
                                        :src="$wire.currencies[selectedCode].icon"
                                        :alt="selectedCode"
                                        class="w-6 h-6 mr-2 rounded-full"
                                    >
                                    <span x-text="$wire.currencies[selectedCode].name + ' (' + selectedCode + ')'"></span>
                                </div>
                            </template>
                            <template x-if="!selectedCode">
                                <span>{{ __('Select a currency') }}</span>
                            </template>
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <!-- Dropdown Menu (Only visible when open is true) -->
                    <div
                        x-show="open"
                        @click.away="open = false"
                        class="absolute w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto"
                        x-transition
                    >
                        <!-- Search Input -->
                        <div class="p-2 sticky top-0 bg-white">
                            <input
                                x-model="search"
                                type="text"
                                placeholder="Search currencies..."
                                class="w-full p-2 border border-gray-500 rounded-md focus:border-gray-500 focus:ring-0 focus:outline-none"
                                @focus="$event.target.select()"
                                @keydown.enter.prevent="if ($el.querySelector('.currency-item:not(.hidden)')) $el.querySelector('.currency-item:not(.hidden)').click()"
                            />
                        </div>

                        <template x-if="Object.keys($wire.currencies).length > 0">
                            <div>
                                <template x-for="(currency, code) in $wire.currencies" :key="code">
                                    <div
                                        x-show="!search || currency.name.toLowerCase().includes(search.toLowerCase()) || code.toLowerCase().includes(search.toLowerCase())"
                                        @click="selectCurrency(code)"
                                        class="flex items-center p-3 hover:bg-gray-100 cursor-pointer currency-item"
                                        :class="{'bg-gray-100': selectedCode === code}"
                                    >
                                        <img
                                            :src="currency.icon"
                                            :alt="code"
                                            class="w-6 h-6 mr-2 rounded-full"
                                        >
                                        <div>
                                            <span x-text="currency.name + ' (' + code + ')'"></span>
                                            <div class="text-xs text-gray-500" x-text="'Rank: ' + currency.rank + ' • $' + currency.rate.toFixed(2)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="Object.keys($wire.currencies).length === 0">
                            <div class="p-3 text-gray-500">{{ __('No currencies available.') }}</div>
                        </template>
                    </div>

                    <!-- Enhanced Selected Currency Display (Always visible when a currency is selected, regardless of dropdown state) -->
                    <template x-if="selectedCode && $wire.currencies[selectedCode]">
                        <div class="mt-2 p-4 bg-gray-100 rounded-md">
                            <div class="flex items-center space-x-2">
                                <img
                                    :src="$wire.currencies[selectedCode].icon"
                                    :alt="selectedCode"
                                    class="w-8 h-8 rounded-full"
                                >
                                <div>
                                    <span class="text-lg font-medium" x-text="$wire.currencies[selectedCode].name + ' (' + selectedCode + ')'"></span>
                                    <div class="text-sm text-gray-600">
                                        <p x-text="'Rank: ' + $wire.currencies[selectedCode].rank"></p>
                                        <p x-text="'Price: $' + $wire.currencies[selectedCode].rate.toFixed(2)"></p>
                                        <p x-text="'24h Volume: $' + $wire.currencies[selectedCode].volume.toLocaleString()"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="inline-block bg-green-500 text-white mt-5 px-4 py-2 rounded-lg shadow-md hover:bg-gray-950 disabled:bg-gray-400 disabled:cursor-not-allowed"
                    :disabled="!$wire.selectedCurrency"
                >
                    {{ __('Create Wallet') }}
                </button>
            </form>
        @else
            <div class="mb-4 mt-5 text-green-500">
                <p>{{ __('You already have a wallet associated with your account.') }}</p>
                <p>{{ __('You can manage your wallet from the dashboard.') }}</p>
            </div>
        @endif
    </div>
    @include('partials.footer')
</section>
