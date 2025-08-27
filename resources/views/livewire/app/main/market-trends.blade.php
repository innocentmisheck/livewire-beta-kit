<div wire:poll.60s="fetchMarketTrends"  id="market-trends"
     class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4 shadow-lg relative">
    @include('partials.notification')
    <!-- Market Trends Card -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Market</h3>
        @if (!empty($cryptos))
            <div class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">
                Market: {{ number_format($marketAverage, 2) }}%
            </div>
        @endif
    </div>
    <div class="relative h-25 overflow-y-auto pr-2">
        <!-- Loading Spinner -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center h-25">
            <div class="flex flex-col items-center justify-center h-25">
                <div class="w-4 h-4 border-3 border-t-transparent border-gray-500 dark:border-gray-400 rounded-full animate-spin"></div>
            </div>
        </div>

        <!-- Data or Error -->
        <div wire:loading.remove>
            @if (empty($cryptos))
                <div class="flex flex-col items-center justify-center h-25">
                    <p class="text-center text-gray-500 dark:text-gray-400">No market data available</p>
                </div>
            @else
                <div class="h-25 overflow-y-auto pr-2">
                    @foreach ($cryptos as $symbol => $data)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 truncate">
                            <span class="font-bold">{{ $symbol }}</span>: ${{ number_format($data['price'], 2) }}
                            <span class="{{ $data['change'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                ({{ number_format($data['change'], 2) }}%)
                            </span>
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    @if (!empty($cryptos))
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 text-right">{{ \Carbon\Carbon::parse($lastUpdated)->diffForHumans() }}</p>
    @endif
</div>
