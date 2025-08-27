<div wire:poll.300s="fetchData" wire:mount="fetchData" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-3 relative">
    <!-- Single Loading Spinner -->
    <div wire:loading class="absolute inset-0 flex justify-center items-center bg-opacity-50 bg-gray-100 dark:bg-opacity-50 dark:bg-neutral-800 z-10">
        <svg class="animate-spin h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="sr-only">Loading data</span>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="mb-2 p-2 bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200 rounded text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-2 p-2 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Active Orders Section -->
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Orders</h3>
        <a href="#" class="text-sm text-blue-500 hover:underline dark:text-blue-400">View All</a>
    </div>
    <ul class="divide-y divide-gray-200 dark:divide-neutral-700 mt-1">
        @forelse ($activeOrders as $order)
            <li class="py-1 flex justify-between items-center text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">
                        <span class="{{ $order['type'] === 'buy' ? 'text-green-500' : 'text-red-500' }}">{{ ucfirst($order['type']) }}</span>
                        {{ number_format($order['amount'], 2) }} {{ $order['currency'] }} @ ${{ number_format($order['price'], 2) }}  
                        {{-- <sup class="{{ $order['status'] === 'filled' ? 'text-green-500' : ($order['status'] === 'pending' ? 'text-yellow-500' : 'text-gray-500') }} text-xs">
                            {{ ucfirst($order['status']) }}
                        </sup> --}}
                    </span>
                    <span class="block text-xs text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($order['timestamp'])->diffForHumans() }}
                      
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                   
                        @if ($order['status'] === 'pending')
                        <div class="flex space-x-3">
                            <!-- Cancel Order Button -->
                            <button wire:click="cancelOrder('{{ $order['id'] }}')" 
                                    wire:loading.attr="disabled" 
                                    class="text-red-500 hover:text-red-600 hover:border-b-red-500 text-xs cursor-pointer">
                                Cancel
                                <span wire:loading class="ml-2 text-gray-500">Loading...</span>
                            </button>
                    
                            <!-- Open Order Button -->
                            <a href="{{ route('profile.wallet.checkout', ['id' => $order['id']]) }}" 
                            class="text-green-500 hover:text-red-600 hover:border-b-green-500 text-xs cursor-pointer">
                                Open 
                            </a>
                        </div>
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
        <a href="#" class="text-sm text-blue-500 hover:underline dark:text-blue-400">View All</a>
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