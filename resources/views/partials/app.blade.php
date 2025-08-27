<x-layouts.app title="Coins - App">



<section class="w-full" x-data="{ isLoading: true }" x-init="setTimeout(() => isLoading = false, 0)">
    @include('partials.mobile.mobile-market-bar')

    <div id="loading-spinner" class="flex top-10 justify-center items-center">
        <div class="w-6 h-6 border-3 border-t-transparent border-green-500 dark:border-green-400 rounded-full animate-spin duration-1000"></div>
    </div>

    <div 
    x-data="{ isLoading: true }" 
    x-init="setTimeout(() => isLoading = false, 0)"
    x-show="isLoading" 
    class="fixed inset-0 flex justify-center items-center bg-white bg-opacity-1 z-50" 
    x-transition.opacity
    >
    </div>


<div id="content" style="display: none;" class="relative min-h-screen">
    @include('partials.app-notifications')

        <!-- Dashboard Content -->
        <div  x-show="!isLoading"
             x-transition:enter="transition ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="flex flex-col gap-4 p-4"
             x-init="$nextTick(() => { $dispatch('chart-loaded', {}); })"
             @chart-loaded.window="isLoading = false">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <livewire:balance-wallet />
                <livewire:market-trends />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4"
         x-init="$nextTick(() => { $dispatch('chart-loaded', {}); })"
             @chart-loaded.window="isLoading = false">
                <livewire:live-trading-chart />
                <livewire:all-live-trading-chart />
            </div>
         
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-15"
            x-init="$nextTick(() => { $dispatch('chart-loaded', {}); })"
            @chart-loaded.window="isLoading = false">
                <livewire:recent-transactions />
                @livewire('orders-and-news')
            </div>
        </div>
    </div>

    @include('partials.footer')
</x-layouts.app>

<script>
    function setupMouseMovementTracker() {
        let lastMouseMove = Date.now();
        const inactivityThreshold = 30000; // 60 seconds (adjust as needed)
        
        // Update last movement time when mouse moves
        document.addEventListener('mousemove', function(e) {
            lastMouseMove = Date.now();
        });
    
        // Check for inactivity every second
        setInterval(function() {
            const timeSinceLastMove = Date.now() - lastMouseMove;
            
            if (timeSinceLastMove >= inactivityThreshold) {
                // Show loading spinner and reload
                showLoadingSpinner();
                setTimeout(function() {
                    window.location.reload();
                }, 1000); // Small delay to show spinner before reload
            }
        }, 1000); // Check every second
    
        // Your existing loading spinner function
        function showLoadingSpinner() {
            document.getElementById('loading-spinner').style.display = 'flex';
            document.getElementById('content').style.display = 'none';
        }
    }
    
    // Initialize everything when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Your existing timeout code
        setTimeout(function() {
            document.getElementById('loading-spinner').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        }, 1000);
    
        // Start the mouse movement tracker
        setupMouseMovementTracker();
    });
</script>
</section>
