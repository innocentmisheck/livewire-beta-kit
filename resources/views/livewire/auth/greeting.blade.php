<div wire:poll.3600s="setGreeting" class="sticky top-2 z-50 bg-white/30 backdrop-blur-md shadow-md p-4 rounded-xl flex items-center justify-between w-full mb-5">
    <div class="flex items-center space-x-3">
        @if (Auth::check() && Auth::user()->avatar)
            <img src="{{ Auth::user()->avatar }}" alt="Avatar" class="w-12 h-12 rounded-full">
        @endif

        <div>
            <h1 class="text-lg md:text-2xl font-bold text-gray-900 dark:text-white">{{ $greeting }}</h1>
            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $cryptoCount }} cryptocurrencies available</p>

        </div>
    </div>

    <!-- Responsive Navigation Tabs -->
    <nav class="hidden md:flex space-x-6">
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">Market</a>
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">News</a>
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">Watchlist</a>
    </nav>

    <!-- Mobile Menu Button -->
    <div class="md:hidden">
        <button id="menuToggle" class="text-gray-700 dark:text-gray-300 focus:outline-none">
            <x-app-logo />
        </button>
    </div>

    <!-- Mobile Menu -->
    <div id="mobileMenu" class="hidden md:hidden fixed top-16 left-0 w-full bg-white dark:bg-gray-900 rounded-xl shadow-lg p-4 flex flex-col space-y-4">
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">Market</a>
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">News</a>
        <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-green-500 font-semibold">Watchlist</a>
    </div>

</div>



<script>
    document.getElementById('menuToggle').addEventListener('click', function () {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
</script>
