<div x-data="{ show: false, message: '', type: 'success' }"
     x-show="show"
     x-transition.opacity
     x-init="
        if (navigator.onLine) {
            message = 'Market Trends';
            type = 'success';
            show = true;
            setTimeout(() => show = false, 3000);
        } else {
            message = 'No internet connection.';
            type = 'error';
            show = true;
        }
     "
     class="fixed bottom-5 left-1/2 transform -translate-x-1/2 z-50 flex items-center gap-5 justify-between
            px-4 py-2 w-80 rounded-xl shadow-lg backdrop-blur-md bg-opacity-70"
     :class="{
         'bg-green-500 text-white': type === 'success',
         'bg-red-500 text-white': type === 'error'
     }">

    <div class="flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path x-show="type === 'success'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            <path x-show="type === 'error'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
        <span x-text="message"></span>
    </div>

    <button @click="show = false"
            class="p-1 border border-white rounded-full bg-transparent bg-opacity-20 ml-5 hover:bg-opacity-40 focus:outline-none">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>

