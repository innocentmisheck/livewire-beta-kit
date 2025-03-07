<div wire:poll.300s="fetchData" wire:mount="fetchData"  class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-3 relative">
    <!-- Single Loading Spinner -->
    <div wire:loading class="absolute inset-0 flex justify-center items-center bg-opacity-5 z-10">
        <span class="sr-only">Loading data</span>
    </div>

    <!-- Active Orders Section -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Active Orders</h3>
        <a class="text-sm text-blue-500 hover:underline dark:text-blue-400">View All</a>
    </div>
    <ul class="divide-y divide-gray-200 dark:divide-neutral-700 mt-1">
        @forelse ($activeOrders as $order)
            <li class="py-1 flex justify-between items-center text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">
                        <span class="{{ $order['type'] === 'buy' ? 'text-green-500' : 'text-red-500' }}">{{ ucfirst($order['type']) }}</span>
                        {{ number_format($order['amount'], 4) }} {{ $order['currency'] }} @ ${{ number_format($order['price'], 2) }}
                    </span>
                    <span class="block text-xs text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($order['timestamp'])->diffForHumans() }}
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="{{ $order['status'] === 'completed' ? 'text-green-500' : 'text-yellow-500' }} text-xs">
                        {{ ucfirst($order['status']) }}
                    </span>
                    @if ($order['status'] === 'pending')
                        <a wire:click="cancelOrder({{ $order['id'] }})" class="text-red-500 hover:text-red-600 text-xs cursor-pointer">Cancel</a>
                    @endif
                </div>
            </li>
        @empty
            <li class="py-1 text-sm text-gray-500 dark:text-gray-400">No active orders</li>
        @endforelse
    </ul>

    <!-- Market News Section -->
    <div class="flex justify-between items-center mt-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Market News</h3>
        <a class="text-sm text-blue-500 hover:underline dark:text-blue-400">View All</a>
    </div>
    <ul class="divide-y divide-gray-200 dark:divide-neutral-700 mt-1">
        @forelse ($marketNews as $news)
            <li class="py-1 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $news['description'] }}</span>
                <span class="block text-xs text-gray-400 dark:text-gray-500">
                    {{ \Carbon\Carbon::parse($news['created_at'])->diffForHumans() }}
                </span>
            </li>
        @empty
            <li class="py-1 text-sm text-gray-500 dark:text-gray-400">No recent news</li>
        @endforelse
    </ul>
</div>
