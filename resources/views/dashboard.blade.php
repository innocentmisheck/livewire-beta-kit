<x-layouts.app title="Coins - App">

    <!-- Spinner for loading -->
    <div id="loading-spinner" class="flex top-10 justify-center items-center">
    <div class="w-6 h-6 border-3 border-t-transparent border-green-500 dark:border-green-400 rounded-full animate-spin"></div>
    </div>

<div class="relative min-h-screen" x-data="{ isLoading: true }" id="content" style="display: none;" >
        <!-- Loading Animation -->
        <div x-show="isLoading"
             x-transition:leave="transition ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-100 dark:bg-neutral-900">
            <div class="w-6 h-6 border-3 border-t-transparent border-green-500 dark:border-green-400 rounded-full animate-spin"></div>
            <span class="sr-only">Loading dashboard</span>
        </div>

        <!-- Dashboard Content -->
        <div x-show="!isLoading"
             x-transition:enter="transition ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="flex flex-col gap-4 p-4"
             x-init="$nextTick(() => { $dispatch('chart-loaded', {}); })"
             @chart-loaded.window="isLoading = false">
            <livewire:greeting />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <livewire:balance-wallet />
                <livewire:market-trends />
            </div>
            <livewire:live-trading-chart />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <livewire:recent-transactions />
                <livewire:orders-and-news />
            </div>
        </div>
    </div>

    @include('partials.footer')
</x-layouts.app>

