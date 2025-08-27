<div wire:poll.60s="fetchTransactions" class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 p-4">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Transactions</h3>
        <a href="/wallet/settings" class="text-sm cursor-pointer text-blue-500 hover:underline dark:text-blue-400">View All</a>
    </div>
    <div class="relative">
        <!-- Loading Spinner -->
        <div wire:loading class="absolute inset-0 flex justify-center items-center">
            <div class="flex flex-col items-center justify-center h-24">
                <div class="w-4 h-4 border-3 border-t-transparent border-gray-500 dark:border-gray-400 rounded-full animate-spin"></div>
            </div>
        </div>

        <!-- Transactions List -->
        <ul wire:loading.remove class="divide-y divide-gray-200 dark:divide-neutral-700 mt-2">
            @forelse ($transactions as $transaction)
                <li class="py-2 flex justify-between items-center text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ ucfirst($transaction['type']) }}
                            {{ number_format($transaction['amount'], 2) }} {{ $transaction['currency'] }}
                            @if($transaction['type'] === 'sent' && $transaction['to'])
                                to {{ $transaction['to'] }}
                            @elseif($transaction['type'] === 'received' && $transaction['from'])
                                from {{ $transaction['from'] }}
                            @endif
                        </span>
                        <span class="block text-xs text-gray-400 dark:text-gray-500">
                            {{ \Carbon\Carbon::parse($transaction['timestamp'])->diffForHumans() }}
                        </span>
                    </div>
                    <span class="{{ $transaction['status'] === 'completed' ? 'text-green-500' : 'text-yellow-500' }} font-medium">
                        {{ ucfirst($transaction['status']) }}
                    </span>
                </li>
            @empty
                <li class="py-2 text-sm text-gray-500 dark:text-gray-400">No recent transactions</li>
            @endforelse
        </ul>
    </div>
</div>