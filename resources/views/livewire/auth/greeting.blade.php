<div wire:poll.60s="setGreeting" class="sticky top-2 z-50 bg-white/30 backdrop-blur-md shadow-md p-2 rounded-xl flex items-center justify-between w-full mb-5 relative">
    
    <!-- Existing content remains the same -->
    <div class="flex items-center space-x-3">
        @if (Auth::check() && Auth::user()->avatar)
            <img src="{{ Auth::user()->avatar }}" alt="Avatar" class="w-12 h-12 rounded-full">
        @endif

        <div>
            <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">{{ $greeting }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">500 cryptocurrencies available</p>
        </div>
    </div>

    @include('partials.mobile.mobile-nav')
</div>

