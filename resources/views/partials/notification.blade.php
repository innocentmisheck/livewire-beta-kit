@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity
         x-init="setTimeout(() => show = false, 3000)"
         class="fixed  top-2 left-1/2 transform -translate-x-1/2 z-50 flex items-center gap-5 justify-between
                bg-green-500 text-white px-4 py-2 w-80 rounded-xl shadow-lg
                backdrop-blur-md bg-opacity-70">
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        <button @click="show = false"
                class="p-1 border border-white rounded-full bg-white bg-opacity-20 ml-5 hover:bg-opacity-40 focus:outline-none">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
@endif
