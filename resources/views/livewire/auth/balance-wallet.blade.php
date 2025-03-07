<div wire:poll.1s="boot" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 shadow-lg relative">
    <span class="absolute top-2 right-2 bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-lg hover:opacity-100">Active</span>

    @if ($walletBalance === 'N/A')
        <div class="text-red-500 text-center py-2">Unable to fetch wallet data</div>
    @endif

    <div class="flex items-center gap-6">
        <a href="{{ route('dashboard') }}" class="flex items-end" wire:navigate>
            <img src="{{ $walletIcon ?? asset('images/placeholder-coin.svg') }}"
                 alt="{{ $walletCurrency }}"
                 class="w-6 h-6 md:w-10 md:h-10 rounded">
        </a>
        <div class="flex-1">
            <div class="text-right">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Balance Wallet ({{ strtoupper($walletCurrency) }})
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
</div>
