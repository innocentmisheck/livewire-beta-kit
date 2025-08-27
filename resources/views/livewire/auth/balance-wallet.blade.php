<div wire:poll.1s="fetchWalletData" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 shadow-lg relative">
    
    @if ($walletBalance === 'N/A')
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md my-2 mx-2 text-center">
            <p class="font-semibold text-lg">Something went wrong.</p>
            <p class="text-sm">Please try reconnecting...</p>
            <a href="/app" class="mt-3 inline-block bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition">
                Reconnect
            </a>
        </div>
    @else
        <span class="absolute top-2 right-2 bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-lg hover:opacity-100">
            Active
        </span>

        <div class="flex items-center gap-6">
            <a href="{{ route('app') }}" class="flex items-end" wire:navigate>
                <img src="{{ $walletIcon ?? asset('images/favicon.ico') }}"
                     alt="{{ $walletCurrency }}"
                     class="w-6 h-6 md:w-10 md:h-10 rounded">
            </a>
            <div class="flex-1">
                <div class="text-right">
                    <h3 x-data="{ 
                        words: ['Balance', 'Amount', 'Funds', 'Total', 'Holdings', 'Worth'], 
                        currentIndex: 0 
                    }"
                    x-init="setInterval(() => currentIndex = (currentIndex + 1) % words.length, 1000)" 
                    class="text-xl font-semibold text-gray-900 dark:text-white"
                >
                    <span x-text="words[currentIndex]"></span> ({{ strtoupper($walletCurrency) }})
                </h3>
                
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $lastUpdated }}</p>
                </div>
                <p class="text-3xl md:text-4xl font-bold text-green-500 mt-2">
                    ${{ $walletBalance }}
                </p>
                <div class="flex items-center gap-2 mt-2">
                    @if ($percentageChange !== 'N/A' && $percentageChange >= 0)
                        <svg class="w-5 h-5 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    @elseif ($percentageChange !== 'N/A')
                        <svg class="w-5 h-5 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    @endif
                    <span class="text-sm md:text-base {{ $percentageChange >= 0 ? 'text-green-600 dark:text-green-500' : 'text-red-600 dark:text-red-500' }}">
                        {{ $percentageChange }}%
                    </span>
                </div>
                <div class="flex justify-end mt-2">
                    <span class="text-lg font-medium text-gray-500 dark:text-gray-400">
                        {{ $btcAmount }} BTC
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
