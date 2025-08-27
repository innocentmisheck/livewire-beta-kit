<section class="w-full" x-data="{ isLoading: true, selectedMethod: null }" x-init="setTimeout(() => isLoading = false, 0)">
    <livewire:greeting />
    @include('partials.checkout.payment-checkout')

     <div 
        x-data="{ isLoading: true }" 
        x-init="setTimeout(() => isLoading = false, 0)"
        x-show="isLoading" 
        class="fixed inset-0 flex justify-center items-center bg-white bg-opacity-0 z-50" 
        x-transition.opacity
        >
        <div class="w-6 h-6 border-3 border-t-transparent top-0 border-green-500 dark:border-green-400 rounded-full animate-spin transition-opacity duration-1000"></div>
     </div>
    
    <div class="container max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Notification -->
        @if (session()->has('notification'))
            <div class="flex items-center mb-8 p-4 rounded-lg shadow-md {{ session('notification.type') === 'success' ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' }} transition-all duration-300">
                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ session('notification.type') === 'success' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}" />
                </svg>
                <span>{{ session('notification.message') }}</span>
            </div>
        @endif
        <!-- Grid Container -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center justify-center min-h-[400px]">
            <!-- Transaction Summary (Left Column) -->
            <div class="lg:col-span-1 flex justify-center lg:justify-start items-center">
                <div class="w-full max-w-md bg-white dark:bg-neutral-800 p-6 rounded-xl shadow-lg border border-gray-200 dark:border-neutral-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">{{ __('Summary') }}</h2>
                    <div class="space-y-4 text-gray-700 dark:text-gray-300">
                        <p class="flex justify-between">
                            <span>{{ __('Crypto') }}</span>
                            <span class="font-medium">{{ number_format($transaction->price, 2) }} {{ $transaction->crypto }}</span>
                        </p>
                        <p class="flex justify-between">
                            <span>{{ __('USD') }}</span>
                            <span class="font-medium">${{ number_format($transaction->dollar, 2) }}</span>
                        </p>
                        <p class="flex justify-between">
                            <span>{{ __('Amount') }}</span>
                            <span class="font-medium">{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}</span>
                        </p>
                        <p class="flex justify-between">
                            <span>{{ __('Rate') }}</span>
                            <span class="font-medium">1 {{ $transaction->crypto }} = {{ number_format($transaction->rate * ($fiatCurrency === 'USD' ? 1 : $this->calculateFiatAmount() / $transaction->dollar), 4) }} {{ $fiatCurrency }}</span>
                        </p>
                        <p class="flex justify-between">
                            <span>{{ __('Status') }}</span>
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $transaction->status == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $transaction->status == 'completed' ? __('Transaction completed successfully') : __('Transaction is pending') }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payment Methods / Form (Right Column) -->
            <div class="lg:col-span-2 flex justify-center items-center">
                <div class="w-full max-w-lg bg-white dark:bg-neutral-800 p-4 rounded-xl shadow-lg border border-gray-200 dark:border-neutral-700 flex flex-col">
                    <h2 class="text-lg text-right font-semibold mb-4 dark:text-white">{{ __('Payment Method') }}</h2>

                    <!-- Payment Method Options (Shown Initially) -->
                    <div x-show="!selectedMethod" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8 w-full transition-opacity duration-300" x-transition.opacity>
                        <!-- Credit/Debit Card -->
                        <div 
                            @click="selectedMethod = 'card'" 
                            class="p-4 border rounded-lg cursor-pointer transition-all duration-200"
                            :class="{ 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500': selectedMethod === 'card', 'border-gray-300 hover:border-gray-400 dark:border-neutral-600 dark:hover:border-neutral-500': selectedMethod !== 'card' }"
                        >
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h5v-2H4v-6h18V6c0-1.11-.89-2-2-2m0 4H4V6h16zm-5.07 11.17l-2.83-2.83l-1.41 1.41L14.93 22L22 14.93l-1.41-1.41z"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ __('Credit/Debit Card') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Visa, Mastercard, Amex</p>
                                </div>
                            </div>
                        </div>

                        <!-- PayPal -->
                        <div 
                            @click="selectedMethod = 'paypal'" 
                            class="p-4 border rounded-lg cursor-pointer transition-all duration-200"
                            :class="{ 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500': selectedMethod === 'paypal', 'border-gray-300 hover:border-gray-400 dark:border-neutral-600 dark:hover:border-neutral-500': selectedMethod !== 'paypal' }"
                        >
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                    <path fill="#0079C1" d="M9.93 12.99c.1 0 2.42.1 3.8-.24h.01c1.59-.39 3.8-1.51 4.37-5.17c0 0 1.27-4.58-5.03-4.58H7.67c-.49 0-.91.36-.99.84L4.38 18.4c-.05.3.19.58.49.58H8.3l.84-5.32c.06-.38.39-.67.79-.67"/>
                                    <path fill="#253B80" d="M18.99 8.29c-.81 3.73-3.36 5.7-7.42 5.7H10.1l-1.03 6.52c-.04.26.16.49.42.49h1.9c.34 0 .64-.25.69-.59c.08-.4.52-3.32.61-3.82c.05-.34.35-.59.69-.59h.44c2.82 0 5.03-1.15 5.68-4.46c.26-1.34.12-2.44-.51-3.25"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ __('PayPal') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fast and secure</p>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer -->
                        <div 
                            @click="selectedMethod = 'bank'" 
                            class="p-4 border rounded-lg cursor-pointer transition-all duration-200"
                            :class="{ 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500': selectedMethod === 'bank', 'border-gray-300 hover:border-gray-400 dark:border-neutral-600 dark:hover:border-neutral-500': selectedMethod !== 'bank' }"
                        >
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                                    <path fill="black" d="M19 1H9c-1.1 0-2 .9-2 2v3h2V4h10v16H9v-2H7v3c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2M7.01 13.47l-2.55-2.55l-1.27 1.27L7 16l7.19-7.19l-1.27-1.27z"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ __('Bank Transfer') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Paychangu or Malipo</p>
                                </div>
                            </div>
                        </div>

                        <!-- Crypto Payment -->
                        <div 
                            @click="selectedMethod = 'crypto'" 
                            class="p-4 border rounded-lg cursor-pointer transition-all duration-200"
                            :class="{ 'border-green-500 bg-green-50 dark:bg-green-900/20 ring-2 ring-green-500': selectedMethod === 'crypto', 'border-gray-300 hover:border-gray-400 dark:border-neutral-600 dark:hover:border-neutral-500': selectedMethod !== 'crypto' }"
                        >
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                    <g fill="none" fill-rule="evenodd">
                                        <circle cx="16" cy="16" r="16" fill="#efb914" fill-rule="nonzero"/>
                                        <path fill="#fff" d="M21.002 9.855A7.95 7.95 0 0 1 24 15.278l-2.847-.708a5.36 5.36 0 0 0-3.86-3.667c-2.866-.713-5.76.991-6.465 3.806s1.05 5.675 3.917 6.388a5.37 5.37 0 0 0 5.134-1.43l2.847.707a7.97 7.97 0 0 1-5.2 3.385L16.716 27l-2.596-.645l.644-2.575a8 8 0 0 1-1.298-.323l-.643 2.575l-2.596-.646l.81-3.241c-2.378-1.875-3.575-4.996-2.804-8.081s3.297-5.281 6.28-5.823L15.323 5l2.596.645l-.644 2.575a8 8 0 0 1 1.298.323l.643-2.575l2.596.646z"/>
                                    </g>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ __('Cryptocurrency') }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">BTC, ETH, etc.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Payment Form (Shown After Selection in Right Column) -->
                    <div wire:loading class="flex justify-center">
                        <div class="w-6 h-6 border-3 border-t-transparent border-green-500 dark:border-green-400 rounded-full animate-spin duration-5000"></div>
                    </div>

                    <div x-show="selectedMethod" class="w-full transition-opacity duration-300" x-transition.opacity>
                        <div class="flex items-center justify-between mb-4">
                            <button @click="selectedMethod = null" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back
                            </button>                        
                        </div>

                        <!-- Credit/Debit Card Form -->
                        <template x-if="selectedMethod === 'card'">
                            <div class="space-y-4">
                                <div hidden>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                                    <input type="text" readonly :value="'{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}'" class="w-full p-2 border border-gray-300 rounded-md bg-gray-100 focus:border-green-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Card Number') }}</label>
                                    <input type="text" wire:model="cardNumber" placeholder="1234 5672 9012 3456" class="w-full p-2 border border-gray-300 rounded-md focus:border-green-500 focus:ring-0 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Expiry Date') }}</label>
                                        <input type="text" wire:model="cardExpiry" placeholder="MM/YY" class="w-full p-2 border border-gray-300 rounded-md focus:border-green-500 focus:ring-0 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('CVC') }}</label>
                                        <input type="text" wire:model="cardCvc" placeholder="123" class="w-full p-2 border border-gray-300 rounded-md focus:border-green-500 focus:ring-0 dark:border-neutral-600 dark:bg-neutral-700 dark:text-white">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PayPal Form -->
                        <template x-if="selectedMethod === 'paypal'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                                    <input type="text" readonly :value="'{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}'" class="w-full p-2 border border-gray-300 rounded-md bg-gray-100 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300">
                                </div>
                                <p class="text-gray-700 dark:text-gray-300">Click "Confirm Payment" to redirect to PayPal.</p>
                            </div>
                        </template>

                        <!-- Bank Transfer Form -->
                        <template x-if="selectedMethod === 'bank'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount') }}</label>
                                    <input type="text" readonly :value="'{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}'" class="w-full p-2 border border-gray-300 rounded-md bg-gray-100 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300">
                                </div>
                                <p class="text-gray-700 dark:text-gray-300">Bank transfer instructions will be provided after confirmation.</p>
                            </div>
                        </template>

                        <!-- Crypto Payment Form -->
                        <template x-if="selectedMethod === 'crypto'">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Amount to Send') }}</label>
                                    <input type="text" readonly :value="'{{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}'" class="w-full p-2 border border-gray-300 rounded-md bg-gray-100 dark:bg-neutral-700 dark:border-neutral-600 dark:text-gray-300">
                                </div>
                                <p class="text-gray-700 dark:text-gray-300">Send to wallet address: <span class="font-mono">1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa</span> (placeholder).</p>
                            </div>
                        </template>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-2 sm:justify-center lg:justify-start mt-5">
                            <button 
                                wire:click="confirmPayment" 
                                wire:loading.attr="disabled" 
                                class="bg-green-600 text-white px-2 py-2 rounded-lg shadow-md hover:bg-green-700 flex items-center justify-center gap-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200"
                                :disabled="!selectedMethod"
                            >
                                {{ __('Confirm Payment') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <button 
                                wire:click="cancel" 
                                class="bg-red-600 text-white px-2 py-2 rounded-lg shadow-md hover:bg-red-700 flex items-center justify-center gap-2 transition-all duration-200"
                            >
                                {{ __('Cancel') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>